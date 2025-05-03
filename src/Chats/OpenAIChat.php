<?php

namespace LabsLLM\Chats;

use OpenAI;
use OpenAI\Client;
use LabsLLM\Messages\Message;
use LabsLLM\Config\OpenAIConfig;
use LabsLLM\Config\ConfigInterface;

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
     * OpenAI model
     */
    protected string $model;

    /**
     * System message
     */
    protected string $systemMessage;
    
    /**
     * Constructor
     *
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config, array $args)
    {
        parent::__construct($config);
        
        if (!$config instanceof OpenAIConfig) {
            throw new \InvalidArgumentException('Configuration must be an instance of OpenAIConfig');
        }
        
        $this->openaiConfig = $config;
        $this->model = $args['model'];
        $this->systemMessage = $args['systemMessage'];
    }
    
    /**
     * Executes the request to the provider
     *
     * @return void
     */
    protected function execute(string $prompt): void
    {
        $client = OpenAI::client($this->openaiConfig->getApiKey());

        $result = $client->chat()->create([
            'model' => $this->model,
            'messages' => [
                ...($this->systemMessage ? [Message::system($this->systemMessage)] : []),
                Message::user($prompt),
            ],
        ]);

        $this->lastResponse = new Message('assistant', $result->choices[0]->message->content);
    }
} 