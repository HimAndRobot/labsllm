<?php

namespace LabsLLM;

use LabsLLM\Chats\OpenAIChat;
use LabsLLM\Messages\Message;
use LabsLLM\Messages\MessagesBag;
use LabsLLM\Helpers\FunctionHelper;
use LabsLLM\Contracts\ChatInterface;
use LabsLLM\Parameters\ObjectParameter;
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
    protected ObjectParameter $outputStructure;

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
     * @param FunctionHelper $tool Tool definition
     * @return self
     */
    public function addTool(FunctionHelper $tool): self
    {
        $this->tools[] = $tool;
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
     * @param ObjectParameter $outputStructure The output structure
     * @return self
     */
    public function withOutputStructure(ObjectParameter $outputStructure): self   
    {
        $this->outputStructure = $outputStructure;
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
            ...(isset($this->systemMessage) ? [Message::system($this->systemMessage)] : []),
            Message::user($prompt)
        ]);
        
        $this->currentStep = 0;
        $this->calledTools = [];
        
        $this->executeChat($messagesBag);

        return $this;
    }

    /**
     * Sends current messages to the LLM and processes the response
     *
     * @param MessagesBag $messagesBag The messages to send to the LLM
     * @return self
     * @throws \Exception If the provider is not set
     */
    public function executeChat(MessagesBag $messagesBag): self
    {
        $this->messagesBag = $messagesBag;
        if (!isset($this->provider)) {
            throw new \Exception('Provider not set. Use the using() method before executing the prompt.');
        }

        $response = $this->chatProvider->executePrompt([
            'messages' => $messagesBag->toArray(),
            'tools' => array_map(function (FunctionHelper $tool) {
                return $tool->toArray();
            }, $this->tools),
            ...(isset($this->outputStructure) ? ['output_structure' => $this->outputStructure->mountBody()] : [])
        ]);

        $this->currentStep++;
        $this->processResponse($response);
        
        return $this;
    }

    /**
     * Processes the response from the LLM
     *
     * @param array $response The response from the LLM
     * @return void
     */
    private function processResponse(array $response): void
    {
        $this->lastResponse = $response;

        if ($response['type'] === 'text') {
            $this->messagesBag->add(Message::assistant($response['response']));
        } else if ($response['type'] === 'tool') {
            $this->executeTool($response['tools'], $response['rawResponse']);
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
    private function executeTool(array $tools, array $rawResponse): void
    {
        $this->messagesBag->add(Message::assistant(null, (array) $rawResponse)); 

        foreach ($tools as &$toolResponse) {
            $filteredTools = array_filter($this->tools, function ($tool) use ($toolResponse) {
                return $tool->getName() === $toolResponse['name'];
            });
            $tool = !empty($filteredTools) ? reset($filteredTools) : null;

            if (!$tool) {
                throw new \Exception('Failed to execute tool: ' . $toolResponse['name'] . ' (tool not found)');
            }

            $response = $tool->execute($toolResponse['arguments']);
            $this->messagesBag->add(Message::tool($response['response'], $toolResponse['id'])); 
            $toolResponse['response'] = $response;
            $this->calledTools[] = $toolResponse;
            $this->lastResponse['tools'] = $tools;
        }

        if ($this->currentStep < $this->maxSteps) {
            $this->executeChat($this->messagesBag);
        }
    }

    /**
     * Gets the complete response data including text and tool calls
     *
     * @return \stdClass Object containing response text and tool information
     */
    public function getResponseData(): \stdClass
    {
        $responseObj = new \stdClass();
        $responseObj->response = $this->lastResponse['response'] ?? '';
        $responseObj->function_calls = $this->lastResponse['tools'] ?? [];
        $responseObj->called_tools = $this->calledTools ?? [];

        return $responseObj;
    }
    /** 
     * Get the response with the output structure
     * 
     * @return \stdClass Object containing response text and tool information
     */
    public function getStructureResponse(): \stdClass
    {
        $responseObj = new \stdClass();
        $responseObj->structure = json_decode($this->lastResponse['response']);
        $responseObj->function_calls = $this->lastResponse['tools'] ?? [];
        $responseObj->called_tools = $this->calledTools ?? [];

        return $responseObj;
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