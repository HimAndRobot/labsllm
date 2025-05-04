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
     * @param string $prompt
     * @param array $options 
     * @return array
     */
    public function executePrompt(string $prompt, array $options = []): array;

    /**
     * Process the response from the provider
     *
     * @param object $response
     * @return array
     */
    public function processResponse(object $response): array;
} 