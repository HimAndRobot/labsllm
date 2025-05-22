<?php

namespace LabsLLM\Chats;

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
        $stream = $this->executeGeminiRequestStream($this->parseBodyFormPrompt($options));
        $toolsCalled = [];
        $tokensUsed = [
            'input' => 0,
            'output' => 0,
            'total' => 0
        ];
        foreach ($stream as $response) {
            if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
                yield [
                    'type' => 'text',
                    'response' => $response['candidates'][0]['content']['parts'][0]['text']
                ];
            } 
            elseif (isset($response['candidates'][0]['content']['parts'][0]['functionCall'])) {
                foreach($response['candidates'][0]['content']['parts'] as $part) {
                    if(isset($part['functionCall'])) {
                        $functionCall = $part['functionCall'];
                        $tool = [
                            'id' => 'no-id',
                            'functionCall' => [
                                'name' => $functionCall['name'],
                                'args' => $functionCall['args']
                            ]
                        ];
                        $toolsCalled[] = $tool;
                    }
                }
            }
            
            if (isset($response['usageMetadata'])) {
                $tokensUsed = [
                    'input' => $response['usageMetadata']['promptTokenCount'] ?? 0,
                    'output' => $response['usageMetadata']['candidatesTokenCount'] ?? 0,
                    'total' => $response['usageMetadata']['totalTokenCount'] ?? 0
                ];
            }
        }
        
        if (count($toolsCalled) > 0) {
            yield [
                'type' => 'tool',
                'rawResponse' => $toolsCalled,
                'tools' => $this->processToolForResponse($toolsCalled)
            ];
        }

        yield [
            'type' => 'usage',
            'usage' => $tokensUsed
        ];
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
                'response' => $response->candidates[0]['content']['parts'][0]['text'],
                'tokensUsed' => (object) [
                    'input' => $response->usageMetadata['promptTokenCount'] ?? 0,
                    'output' => $response->usageMetadata['candidatesTokenCount'] ?? 0,
                    'total' => $response->usageMetadata['totalTokenCount'] ?? 0
                ]
            ];
        } else if ($response->candidates[0]['content']['parts'][0]['functionCall']) {
            return [
                'type' => 'tool',
                'rawResponse' => $response->candidates[0]['content']['parts'],
                'tools' => $this->processToolForResponse($response->candidates[0]['content']['parts']),
                'tokensUsed' => (object) [
                    'input' => $response->usageMetadata['promptTokenCount'] ?? 0,
                    'output' => $response->usageMetadata['candidatesTokenCount'] ?? 0,
                    'total' => $response->usageMetadata['totalTokenCount'] ?? 0
                ]
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

    /**
     * Execute a request to Gemini API stream
     *
     * @param array $parameters
     * @return \Generator
     */
    public function executeGeminiRequestStream(array $parameters): \Generator
    {
        $client = new \GuzzleHttp\Client();

        try {
            $response = $client->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:streamGenerateContent?key=" . $this->apiKey,
                [
                    'json'    => $parameters,
                    'stream'  => true
                ]
            );

            $body = $response->getBody();
            $count = 0;
            $buffer = '';
            while (!$body->eof()) {
                $chunk = $body->read(1024);
                $buffer .= trim($chunk);
                
                $pattern = '/"candidates".*?"modelVersion"/s';
                if(preg_match_all($pattern, $buffer, $matches, PREG_OFFSET_CAPTURE)) {
                    foreach($matches[0] as $match) {
                        $jsonString = '{' . substr($match[0], 0, -18) . "}";
                        $responseChunk = json_decode($jsonString, true);
                        $buffer = substr($buffer, $match[1] + 1);
                        yield $responseChunk;
                    }
                }
                $count++;
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }
} 