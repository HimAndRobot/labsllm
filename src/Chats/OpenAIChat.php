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
    public function executePrompt(array $options = []): array
    {
        $client = OpenAI::client($this->apiKey);

        $result = $client->chat()->create([
            'model' => $this->model,
            'messages' => $options['messages'] ?? [],
            ...($options['tools'] ? ['tools' => $options['tools']] : []),
            ...(isset($options['output_structure']) ? ['response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'response',
                    'schema' => $options['output_structure']
                ]
            ]] : [])
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
        } else if ($response->choices[0]->message->toolCalls) {
            return [
                'type' => 'tool',
                'rawResponse' => $response->choices[0]->message->toolCalls,
                'tools' => array_map(function ($tool) {
                    return [
                        'id' => $tool->id,
                        'name' => $tool->function->name,
                        'arguments' => json_decode($tool->function->arguments, true)
                    ];
                }, $response->choices[0]->message->toolCalls)
            ];
        }

        throw new \Exception('No response from OpenAI');
    }
} 