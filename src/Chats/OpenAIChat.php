<?php

namespace LabsLLM\Chats;

use LabsLLM\Config\ConfigInterface;
use LabsLLM\Config\OpenAIConfig;
use LabsLLM\Messages\Message;

/**
 * Chat for the OpenAI provider
 */
class OpenAIChat extends BaseChat
{
    /**
     * OpenAI specific configuration
     */
    protected OpenAIConfig $openaiConfig;
    
    /**
     * Constructor
     *
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config)
    {
        parent::__construct($config);
        
        if (!$config instanceof OpenAIConfig) {
            throw new \InvalidArgumentException('Configuration must be an instance of OpenAIConfig');
        }
        
        $this->openaiConfig = $config;
    }
    
    /**
     * Executes the request to the provider
     *
     * @return void
     */
    protected function execute(): void
    {
        // Here the communication logic with the OpenAI API would be implemented
        // For now, we're simulating a response
        
        $messages = [];
        foreach ($this->messages as $message) {
            $messages[] = $message->toArray();
        }
        
        // Response simulation
        $content = "This is a simulated response from the OpenAI API using the model {$this->openaiConfig->getModel()}";
        $this->lastResponse = Message::assistant($content);
    }
} 