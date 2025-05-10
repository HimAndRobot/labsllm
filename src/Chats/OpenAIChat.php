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

        $result = $client->chat()->create(parameters: $this->parseBodyFormPrompt($options));
        return $this->processResponse($result);
    }

    public function executeStream(array $options = []): \Generator | array
    {
        $client = OpenAI::client($this->apiKey);

        $stream = $client->chat()->createStreamed(parameters: $this->parseBodyFormPrompt($options));
        $toolsCalled = [];
        foreach ($stream as $response) {
            if (isset($response->choices[0]->delta->content)) {
                yield [
                    'type' => 'text',
                    'response' => $response->choices[0]->delta->content
                ];
            } elseif (isset($response->choices[0]->delta->toolCalls)) {
                if (isset($response->choices[0]->delta->toolCalls[0]) && isset($response->choices[0]->delta->toolCalls[0]->id)) {
                    $tool = (object) [
                        'id' => $response->choices[0]->delta->toolCalls[0]->id,
                        'type' => $response->choices[0]->delta->toolCalls[0]->type,
                        'function' => (object) [
                            'name' => $response->choices[0]->delta->toolCalls[0]->function->name,
                            'arguments' => $response->choices[0]->delta->toolCalls[0]->function->arguments
                        ]
                    ];
                    $toolsCalled[] = $tool;
                } else if (isset($response->choices[0]->delta->toolCalls[0])) {
                    $lastTool = array_pop($toolsCalled);
                    $lastTool->function->arguments .= $response->choices[0]->delta->toolCalls[0]->function->arguments;
                    $toolsCalled[] = $lastTool;
                }
            }
        }
        
        if (count($toolsCalled) > 0) {
            yield [
                'type' => 'tool',
                'rawResponse' => $toolsCalled,
                'tools' => $this->processToolForResponse($toolsCalled)
            ];
        }
    }

    /**
     * Parse the body for the prompt
     *
     * @param array $options
     * @return array
     */
    private function parseBodyFormPrompt(array $options): array
    {
        if (isset($options['systemMessage'])) {
            $options['messages'] = array_merge([
                [
                    'role' => 'system',
                    'content' => $options['systemMessage']
                ]
            ], $options['messages']);
        }
        
        return [
            'model' => $this->model,
            'messages' => $options['messages'] ?? [],
            ...($options['tools'] ? ['tools' => $options['tools']] : []),
            ...(isset($options['output_schema']) ? ['response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'response',
                    'schema' => $options['output_schema']
                ]
            ]] : [])
        ];
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
                'tools' => $this->processToolForResponse($response->choices[0]->message->toolCalls)
            ];
        }

        throw new \Exception('No response from OpenAI');
    }

    /**
     * Process the tools for the response
     *
     * @param array $tools
     * @return array
     */
    private function processToolForResponse(array $tools): array
    {
        return array_map(function ($tool) {
                return [
                    'id' => $tool->id,
                    'name' => $tool->function->name,
                    'arguments' => json_decode($tool->function->arguments, true)
            ];
        }, $tools);
    }
} 