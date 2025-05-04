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
     * OpenAI model
     */
    protected string $model;

    /**
     * OpenAI API key
     */
    protected string $apiKey;

    /**
     * System message
     */
    protected string $systemMessage;

    public function __construct(array $args)
    {   
        $this->model = $args['provider']->getModel();
        $this->systemMessage = $args['systemMessage'] ?? '';
        $this->apiKey = $args['provider']->getApiKey();
    }
    
    /**
     * Executes the request to the provider
     *
     * @return void
     */
    protected function execute(string $prompt): void
    {
        $client = OpenAI::client($this->apiKey);

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