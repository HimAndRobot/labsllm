<?php

namespace LabsLLM\Chats;

use OpenAI;
use LabsLLM\Parameters\ArrayParameter;
use LabsLLM\Parameters\ObjectParameter;
use LabsLLM\Parameters\StringParameter;

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

        if(isset($options['output_schema']) && count($options['output_schema']) > 0 && count($options['tools']) > 0) {
            throw new \Exception('Output schema and tools cannot be used together in google provider, is a limitation of the provider');
        }

        return [
            'contents' => $this->parseMessages($options['messages'] ?? []),
            ...(isset($options['systemMessage']) ? ['system_instruction' => [
                'parts' => [
                    [
                        'text' => $options['systemMessage']
                    ]
                ]
            ]] : []),
            ...(isset($options['tools']) && count($options['tools']) > 0 ? ['tools' => [
                'functionDeclarations' => array_map(function ($tool) {
                    return [
                        'name' => $tool['function']['name'],
                        'description' => $tool['function']['description'],
                        ...(isset($tool['function']['parameters']) ? ['parameters' => $tool['function']['parameters']] : [])
                    ];
                }, $options['tools'])
            ]] : []),
            ...(isset($options['output_schema']) ? ['generationConfig' =>[
                'responseMimeType' => 'application/json',
                'responseSchema' => $options['output_schema']
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
        if (isset($response->candidates[0]['content']['parts'][0]['text'])) {
            return [
                'type' => 'text',
                'response' => $response->candidates[0]['content']['parts'][0]['text']
            ];
        } else if ($response->candidates[0]['content']['parts'][0]['functionCall']) {
            return [
                'type' => 'tool',
                'rawResponse' => $response->candidates[0]['content']['parts'],
                'tools' => $this->processToolForResponse($response->candidates[0]['content']['parts'])
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
                    'id' => 'no-id',
                    'name' => $tool['functionCall']['name'],
                    'arguments' => $tool['functionCall']['args']
            ];
        }, $tools);
    }

    /**
     * Parse the messages for the request
     *
     * @param array $messages
     * @return array
     */
    private function parseMessages(array $messages): array
    {
        return array_map(function ($message) {
            return [
                'role' => match ($message['role']) {
                    'assistant' => 'model',
                    'user'      => 'user', 
                    'tool'      => 'user',
                    default     => $message['role']
                },
                'parts' => match ($message['role']) {
                    'tool' => [
                        [
                            'functionResponse' => [
                                'name'     => $message['tool_name'],
                                'response' => [
                                    'result' => $message['content'] 
                                ]
                            ]
                        ]
                    ],
                    default => call_user_func(function () use ($message): array {
                        if (isset($message['content'])) {
                            return [
                                [
                                    'text' => $message['content']
                                ]
                            ];
                        }

                        if (!empty($message['tool_calls']) && is_array($message['tool_calls'])) {
                            return array_map(function ($toolCall) {
                                $functionCallData = [
                                    'name' => $toolCall['functionCall']['name']
                                ];
                                if (!empty($toolCall['functionCall']['args'])) {
                                    $functionCallData['args'] = $toolCall['functionCall']['args'];
                                }
                                return ['functionCall' => $functionCallData];
                            }, $message['tool_calls']);
                        }

                        return [];
                    })
                }
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