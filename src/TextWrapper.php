<?php

namespace LabsLLM;

use LabsLLM\Chats\OpenAIChat;
use LabsLLM\Messages\Message;
use LabsLLM\Messages\MessagesBag;
use LabsLLM\Helpers\FunctionHelper;
use LabsLLM\Contracts\ChatInterface;
use LabsLLM\Contracts\ProviderInterface;

/**
 * Factory for creating chat instances
 */
class TextWrapper
{

    /**
     * The provider
     * @var ProviderInterface
     */
    protected ProviderInterface $provider;

    /**
     * The chat provider
     * @var ChatInterface
     */
    protected ChatInterface $chatProvider;

    /**
     * The messages
     * @var MessagesBag
     */
    protected MessagesBag $messagesBag;

    /**
     * The last response
     * @var array
     */
    protected array $lastResponse;

    /**
     * The system message
     * @var string
     */
    protected string $systemMessage;

    /**
     * The tools
     * @var array<FunctionHelper>
     */
    protected array $tools;
    

    /**
     * Execute the prompt
     *
     * @param string $prompt
     * @return self
     */
    public function executePrompt(string $prompt): self
    {

        if (!isset($this->provider)) {
            throw new \Exception('Provider not set select a provider before execute the prompt');
        }

        $this->messagesBag = MessagesBag::create([
            ...(isset($this->systemMessage) ? [Message::system($this->systemMessage)] : []),
            Message::user($prompt)
        ]);
        

        $response = $this->chatProvider->executePrompt($prompt, [
            'messages' => $this->messagesBag->toArray(),
            'tools' => array_map(function (FunctionHelper $tool) {
                return $tool->toArray();
            }, $this->tools)
        ]);

        $this->messagesBag->add(Message::assistant($response['response']));
        $this->lastResponse = $response;

        return $this;
    }

    /**
     * Add a tool
     *
     * @param FunctionHelper $tool
     * @return self
     */
    public function addTool(FunctionHelper $tool): self
    {
        $this->tools[] = $tool;
        return $this;
    }

    /**
     * Get the last response as text
     *
     * @return string
     */
    public function asText(): string
    {
        return $this->lastResponse['response'] ?? '';
    }

    /**
     * Get the messages bag
     *
     * @return MessagesBag
     */
    public function getMessagesBag(): MessagesBag
    {
        return $this->messagesBag;
    }

    /**
     * Sets the provider
     *
     * @param ProviderInterface $provider
     * @return self
     */
    public function using(ProviderInterface $provider): self
    {
        $this->provider = $provider;
        switch ($provider->getName()) {
            case 'openai':
                $this->chatProvider = new OpenAIChat($provider);
                break;
        }

        return $this;
    }

    /**
     * Sets the system message
     *
     * @param string $systemMessage
     * @return self
     */
    public function withSystemMessage(string $systemMessage): self
    {
        $this->systemMessage = $systemMessage;
        return $this;
    }

    /**
     * Gets the system message
     *
     * @return string
     */
    public function getSystemMessage(): string
    {
        return $this->systemMessage;
    }
} 