<?php

namespace LabsLLM\Contracts;

use LabsLLM\Messages\Message;
use LabsLLM\Messages\MessagesBag;

/**
 * Interface for chat implementations
 */
interface ChatInterface
{
    /**
     * Sends a message to the chat
     *
     * @param Message|string $message
     * @return self
     */
    public function send(Message|string $message): self;
    
    /**
     * Adds a system message
     *
     * @param string $message
     * @return self
     */
    public function system(string $message): self;
    
    /**
     * Gets the response as text
     *
     * @return string
     */
    public function asText(): string;

    /**
     * Gets the history of the chat
     *
     * @return MessagesBag
     */
    public function getHistory(): MessagesBag;
} 