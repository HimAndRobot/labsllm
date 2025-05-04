<?php

namespace LabsLLM\Chats;

use OpenAI;

/**
 * Chat for the OpenAI provider
 */
class OpenAIChat extends BaseChat
{   
    /**
     * Executes the request to the provider
     *
     * @return void
     */
    public function executePrompt(string $prompt, array $options = []): array
    {
        $client = OpenAI::client($this->apiKey);

        $result = $client->chat()->create([
            'model' => $this->model,
            'messages' => $options['messages'] ?? []
        ]);
        
        return $this->processResponse($result);
    }

    /**
     * Process the response from the provider
     *
     * @param object $response
     * @return array
     */
    public function processResponse(object $response): array
    {
        if ($response->choices[0]->message->content) {
            return [
                'type' => 'text',
                'response' => $response->choices[0]->message->content
            ];
        }

        throw new \Exception('No response from OpenAI');
    }
} 