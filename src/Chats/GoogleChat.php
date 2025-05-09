<?php

namespace LabsLLM\Chats;

use OpenAI;

/**
 * Chat for the Google provider
 */
class GoogleChat extends BaseChat
{   
    /**
     * Executes the request to the provider
     *
     * @return void
     */
    public function executePrompt(array $options = []): array
    {
        $result = $this->executeGeminiRequest($this->parseBodyFormPrompt($options));
        return $this->processResponse((object) $result);
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
        return [
            'contents' => $this->parseMessages($options['messages'] ?? []),
            ...(isset($options['systemMessage']) ? ['system_instruction' => [
                'parts' => [
                    [
                        'text' => $options['systemMessage']
                    ]
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
        if ($response->candidates[0]['content']['parts'][0]['text']) {
            return [
                'type' => 'text',
                'response' => $response->candidates[0]['content']['parts'][0]['text']
            ];
        } else if ($response->candidates[0]['tool_calls']) {
            return [
                'type' => 'tool',
                'rawResponse' => $response->candidates[0]->tool_calls,
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

    private function parseMessages(array $messages): array
    {      
        return array_map(function ($message) {
            return [
                'role' => $message['role'],
                'parts' => [
                    [
                        'text' => $message['content']
                    ]
                ]
            ];
        }, $messages);
    }

    /**
     * Execute a request to Gemini API
     *
     * @param array $history The conversation history
     * @param string $context The system context/instruction
     * @return array
     */
    public function executeGeminiRequest(array $parameters): array
    {
        $client = new \GuzzleHttp\Client();

        $response = $client->post("https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key=" . $this->apiKey, [
            'json' => $parameters
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
} 