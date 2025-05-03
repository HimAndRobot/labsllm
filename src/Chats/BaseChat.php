<?php

namespace LabsLLM\Chats;

use LabsLLM\Config\ConfigInterface;
use LabsLLM\Contracts\ChatInterface;
use LabsLLM\Messages\Message;

/**
 * Base class for all chats
 */
abstract class BaseChat implements ChatInterface
{
    /**
     * Chat configuration
     */
    protected ConfigInterface $config;
    
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
     * Constructor
     *
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }
    
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
     * Executes the request to the provider
     *
     * @return void
     */
    abstract protected function execute(string $prompt): void;
} 