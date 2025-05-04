<?php

namespace LabsLLM\Chats;

use LabsLLM\Messages\Message;
use LabsLLM\Messages\MessagesBag;
use LabsLLM\Config\ConfigInterface;
use LabsLLM\Contracts\ChatInterface;

/**
 * Base class for all chats
 */
abstract class BaseChat implements ChatInterface
{
    
    /**
     * Chat messages
     *
     * @var Message[]
     */
    protected array $messages = [];
    
    /**
     * Last response received
     */
    protected ?Message $lastResponse = null;

    /**
     * chat history
     */
    protected MessagesBag $messagesBag;
    
    /**
     * Sends a message to the chat
     *
     * @param Message|string $message
     * @return self
     */
    public function send(Message|string $prompt): self
    {   
        $this->execute($prompt);
        return $this;
    }
    
    /**
     * Adds a system message
     *
     * @param string $message
     * @return self
     */
    public function system(string $message): self
    {
        $this->messages[] = Message::system($message);
        
        return $this;
    }
    
    /**
     * Gets the response as text
     *
     * @return string
     */
    public function asText(): string
    {
        return $this->lastResponse ? $this->lastResponse->getContent() : '';
    }

    /**
     * Gets the history of the chat
     *
     * @return MessagesBag
     */
    public function getHistory(): MessagesBag
    {
        return $this->messagesBag;
    }
    
    /**
     * Executes the request to the provider
     *
     * @return void
     */
    abstract protected function execute(string $prompt): void;
} 