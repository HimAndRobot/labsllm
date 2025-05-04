<?php

namespace LabsLLM;

use LabsLLM\Chats\OpenAIChat;
use LabsLLM\Contracts\ChatInterface;
use LabsLLM\Messages\Message;
use LabsLLM\Messages\MessagesBag;
use LabsLLM\Contracts\ProviderInterface;

/**
 * Factory for creating chat instances
 */
class TextWrapper
{

    /**
     * The provider
     */
    protected ProviderInterface $provider;

    /**
     * The chat provider
     */
    protected ChatInterface $chatProvider;

    /**
     * The messages
     */
    protected MessagesBag $messagesBag;

    /**
     * The last response
     */
    protected array $lastResponse;

    /**
     * The system message
     */
    protected string $systemMessage;
    

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
            ...($this->systemMessage ? [Message::system($this->systemMessage)] : []),
            Message::user($prompt)
        ]);

        $response = $this->chatProvider->executePrompt($prompt, [
            'messages' => $this->messagesBag->toArray()
        ]);

        $this->messagesBag->add(Message::assistant($response['response']));
        $this->lastResponse = $response;

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