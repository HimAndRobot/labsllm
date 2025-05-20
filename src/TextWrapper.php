<?php

namespace LabsLLM;

use LabsLLM\Chats\GoogleChat;
use LabsLLM\Chats\OpenAIChat;
use LabsLLM\Messages\Message;
use LabsLLM\Messages\MessagesBag;
use LabsLLM\Response\TextResponse;
use LabsLLM\Helpers\FunctionHelper;
use LabsLLM\Contracts\ChatInterface;
use LabsLLM\Response\StreamResponse;
use LabsLLM\Parameters\ObjectParameter;
use LabsLLM\Response\StructureResponse;
use LabsLLM\Contracts\ProviderInterface;

/**
 * TextWrapper - A class for managing interactions with LLMs
 */
class TextWrapper
{
    /**
     * The LLM provider
     * @var ProviderInterface
     */
    protected ProviderInterface $provider;

    /**
     * The chat provider
     * @var ChatInterface
     */
    protected ChatInterface $chatProvider;

    /**
     * System message to contextualize the LLM
     * @var string
     */
    protected string $systemMessage;

    /**
     * Conversation message history
     * @var MessagesBag
     */
    protected MessagesBag $messagesBag;

    /**
     * Available tools for the LLM to use
     * @var array<FunctionHelper>
     */
    protected array $tools = [];

    /**
     * Last response received from the LLM
     * @var array
     */
    protected array $lastResponse = [];

    /**
     * Record of executed tools
     * @var array
     */
    protected array $calledTools = [];

    /**
     * Maximum number of tool execution steps
     * @var int
     */
    protected int $maxSteps = 1;

    /**
     * Current number of steps executed
     * @var int
     */
    protected int $currentStep = 0;

    /**
     * Output structure for the LLM
     * @var ObjectParameter
     */
    protected ObjectParameter $outputSchema;

    /**
     * Sets the LLM provider to be used
     *
     * @param ProviderInterface $provider The provider (OpenAI, Anthropic, etc.)
     * @return self
     */
    public function using(ProviderInterface $provider): self
    {
        $this->provider = $provider;

        switch ($provider->getName()) {
            case 'openai':
                $this->chatProvider = new OpenAIChat($provider);
                break;
            case 'google':
                $this->chatProvider = new GoogleChat($provider);
                break;
            default:
                throw new \Exception('Unsupported provider: ' . $provider->getName());
        }

        return $this;
    }

    /**
     * Sets the system message to contextualize the LLM
     *
     * @param string $systemMessage Instructions/context for the model
     * @return self
     */
    public function withSystemMessage(string $systemMessage): self
    {
        $this->systemMessage = $systemMessage;
        return $this;
    }

    /**
     * Gets the current system message
     *
     * @return string
     */
    public function getSystemMessage(): string
    {
        return $this->systemMessage ?? '';
    }

    /**
     * Adds a tool that can be called by the LLM
     *
     * @param FunctionHelper | array<FunctionHelper> $tool Tool definition
     * @return self
     */
    public function addTool(FunctionHelper | array $tool): self
    {
        if (is_array($tool)) {
            $this->tools = array_merge($this->tools, $tool);
        } else {
            $this->tools[] = $tool;
        }
        return $this;
    }

    /**
     * Sets the maximum number of recursive tool steps
     *
     * @param int $maxSteps Maximum number of steps
     * @return self
     */
    public function withMaxSteps(int $maxSteps): self
    {
        $this->maxSteps = $maxSteps;
        return $this;
    }

    /**
     * Sets the output structure for the LLM
     *
     * @param ObjectParameter $outputSchema The output schema
     * @return self
     */
    public function withOutputSchema(ObjectParameter $outputSchema): self   
    {
        $this->outputSchema = $outputSchema;
        return $this;
    }

    /**
     * Executes an initial prompt, starting the conversation
     *
     * @param string $prompt The initial question or instruction
     * @return self
     * @throws \Exception If the provider is not set
     */
    public function executePrompt(string $prompt): self
    {
        if (!isset($this->provider)) {
            throw new \Exception('Provider not set. Use the using() method before executing the prompt.');
        }

        $messagesBag = MessagesBag::create([
            Message::user($prompt)
        ]);
        
        $this->currentStep = 0;
        $this->calledTools = [];
        
        $this->executeChat($messagesBag);

        return $this;
    }

    public function executePromptStream(string $prompt): \Generator
    {
        $messagesBag = MessagesBag::create([
            Message::user($prompt)
        ]);
        
        yield from $this->executeChatStream($messagesBag);

    }

    private function mountOptions(MessagesBag $messagesBag): array
    {
        return [
            'systemMessage' => $this->systemMessage ?? null,
            'messages' => $messagesBag->toArray(),
            'tools' => array_map(function (FunctionHelper $tool) {
                return $tool->toArray();
            }, $this->tools),
            ...(isset($this->outputSchema) ? ['output_schema' => $this->outputSchema->mountBody()] : [])
        ];
    }

    function executeChat(MessagesBag $messagesBag): self
    {
        $this->messagesBag = $messagesBag;
        if (!isset($this->provider)) {
            throw new \Exception('Provider not set. Use the using() method before executing the prompt.');
        }
        
        $this->currentStep++;

        $response = $this->chatProvider->executePrompt($this->mountOptions($messagesBag));
        $this->processResponse($response);
        return $this;
    }

    /**
     * Sends current messages to the LLM and processes the response
     *
     * @param MessagesBag $messagesBag The messages to send to the LLM
     * @return self
     * @throws \Exception If the provider is not set
     */
    public function executeChatStream(MessagesBag $messagesBag): \Generator
    {
        $acumulatedResponse = '';
        $this->messagesBag = $messagesBag;
        $this->currentStep++;
        $response = $this->chatProvider->executeStream($this->mountOptions($messagesBag));
        foreach ($response as $responseItem) {
            switch ($responseItem['type']) {
                case 'text':
                    $acumulatedResponse .= $responseItem['response'];
                    yield new StreamResponse($responseItem['response'],$acumulatedResponse, [], [], $messagesBag);
                    break;
                case 'tool':
                    $result = $this->executeTool($responseItem['tools'], $responseItem['rawResponse']);
                    yield new StreamResponse('', '', $result['calledTools'], [], $messagesBag);
                    if ($this->currentStep < $this->maxSteps) {
                        yield from $this->executeChatStream($this->messagesBag);
                    }
                    break;
            }
        }

        if ($acumulatedResponse) {
            $this->messagesBag->add(Message::assistant($acumulatedResponse));
        }
        yield new StreamResponse('', $acumulatedResponse, [], [], $messagesBag);
    }

    /**
     * Processes the response from the LLM
     *
     * @param array $response The response from the LLM
     * @return void
     */
    private function processResponse(array $response): void
    {
        $response['tokensUsed'] = (object) [
            'input' => ($response['tokensUsed']->input ?? 0) + ($this->lastResponse['tokensUsed']->input ?? 0),
            'output' => ($response['tokensUsed']->output ?? 0) + ($this->lastResponse['tokensUsed']->output ?? 0),
            'total' => ($response['tokensUsed']->total ?? 0) + ($this->lastResponse['tokensUsed']->total ?? 0)
        ];
        $this->lastResponse = $response;

        switch ($response['type']) {
            case 'text':
                $this->messagesBag->add(Message::assistant($response['response']));
                break;
            case 'tool':
                $result = $this->executeTool($response['tools'], $response['rawResponse']);
                $this->calledTools = $result['calledTools'];
                if ($this->currentStep < $this->maxSteps) {
                    $this->executeChat($this->messagesBag);
                }
                break;
        }
    }

    /**
     * Executes tools requested by the LLM
     *
     * @param array $tools The tools to execute
     * @param array $rawResponse The raw tool call response
     * @return void
     * @throws \Exception If a requested tool is not found
     */
    private function executeTool(array $tools, array $rawResponse): array
    {
        $this->messagesBag->add(Message::assistant(null, (array) $rawResponse)); 
        $calledTools = [];

        foreach ($tools as &$toolResponse) {
            $filteredTools = array_filter($this->tools, function ($tool) use ($toolResponse) {
                return $tool->getName() === $toolResponse['name'];
            });
            $tool = !empty($filteredTools) ? reset($filteredTools) : null;

            if (!$tool) {
                throw new \Exception('Failed to execute tool: ' . $toolResponse['name'] . ' (tool not found)');
            }

            $response = $tool->execute($toolResponse['arguments'] ?? []);
            $this->messagesBag->add(
                Message::tool(
                    $response,
                    $toolResponse['id'],
                    $toolResponse['name'],
                    $tool->getDescription(),
                    $toolResponse['arguments'] ?? [],
                    $response
                )
            ); 

            $toolResponse['response'] = $response;
            $this->lastResponse['tools'] = $tools;
            $calledTools[] = $toolResponse;
        }
        return [
            'success' => true,
            'calledTools' => $calledTools
        ];
    }

    /**
     * Gets the complete response data including text and tool calls
     *
     * @return TextResponse
     */
    public function getResponseData(): TextResponse
    {
        return new TextResponse(
            $this->lastResponse['response'] ?? '',
            $this->lastResponse['tools'] ?? [],
            $this->calledTools ?? [],
            $this->lastResponse['tokensUsed'] ?? new \stdClass()
        );
    }
    /** 
     * Get the response with the output structure and the tool calls
     * 
     * @return StructureResponse
     */
    public function getStructureResponse(): StructureResponse
    {
        if (!isset($this->outputSchema)) {
            throw new \Exception('Output schema not set. Use the withOutputSchema() method before getting the structure response.');
        }

        return new StructureResponse(
            json_decode($this->lastResponse['response'] ?? '{}'),
            $this->lastResponse['tools'] ?? [],
            $this->calledTools ?? [],
        );
    }

    /**
     * Gets the complete message history
     *
     * @return MessagesBag The message history object
     */
    public function getMessagesBag(): MessagesBag
    {
        return $this->messagesBag;
    }
}