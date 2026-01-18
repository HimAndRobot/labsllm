<?php

namespace LabsLLM\Chats;

use GuzzleHttp\Client;
use LabsLLM\Providers\OpenRouter;

/**
 * Chat for the OpenRouter provider
 */
class OpenRouterChat extends BaseChat
{
    private OpenRouter $provider;

    public function __construct(OpenRouter $provider)
    {
        parent::__construct($provider);
        $this->provider = $provider;
    }

    /**
     * Executes the request to the provider
     *
     * @return array
     */
    public function executePrompt(array $options = []): array
    {
        $client = $this->makeClient();
        $response = $client->post('/chat/completions', [
            'json' => $this->parseBodyFormPrompt($options, false)
        ]);

        $result = json_decode($response->getBody()->getContents());
        return $this->processResponse($result);
    }

    public function executeStream(array $options = []): \Generator | array
    {
        $client = $this->makeClient();
        $response = $client->post('/chat/completions', [
            'json' => $this->parseBodyFormPrompt($options, true),
            'stream' => true
        ]);

        $body = $response->getBody();
        $buffer = '';
        $toolsCalled = [];

        while (!$body->eof()) {
            $buffer .= $body->read(1024);

            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $pos));
                $buffer = substr($buffer, $pos + 1);

                if ($line === '' || !str_starts_with($line, 'data:')) {
                    continue;
                }

                $payload = trim(substr($line, 5));
                if ($payload === '[DONE]') {
                    break 2;
                }

                $responseChunk = json_decode($payload);
                if (!$responseChunk) {
                    continue;
                }

                if (isset($responseChunk->choices[0]->delta->content)) {
                    yield [
                        'type' => 'text',
                        'response' => $responseChunk->choices[0]->delta->content
                    ];
                } elseif (isset($responseChunk->choices[0]->delta->tool_calls) || isset($responseChunk->choices[0]->delta->toolCalls)) {
                    $deltaToolCalls = $responseChunk->choices[0]->delta->tool_calls ?? $responseChunk->choices[0]->delta->toolCalls;
                    if (isset($deltaToolCalls[0]) && isset($deltaToolCalls[0]->id)) {
                        $tool = (object) [
                            'id' => $deltaToolCalls[0]->id,
                            'type' => $deltaToolCalls[0]->type,
                            'function' => (object) [
                                'name' => $deltaToolCalls[0]->function->name,
                                'arguments' => $deltaToolCalls[0]->function->arguments
                            ]
                        ];
                        $toolsCalled[] = $tool;
                    } elseif (isset($deltaToolCalls[0])) {
                        $lastTool = array_pop($toolsCalled);
                        $lastTool->function->arguments .= $deltaToolCalls[0]->function->arguments;
                        $toolsCalled[] = $lastTool;
                    }
                } elseif (isset($responseChunk->usage)) {
                    yield [
                        'type' => 'usage',
                        'usage' => [
                            'input' => $responseChunk->usage->prompt_tokens ?? $responseChunk->usage->promptTokens,
                            'output' => $responseChunk->usage->completion_tokens ?? $responseChunk->usage->completionTokens,
                            'total' => $responseChunk->usage->total_tokens ?? $responseChunk->usage->totalTokens
                        ]
                    ];
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
    private function parseBodyFormPrompt(array $options, bool $isStream = false): array
    {
        $messages = $options['messages'] ?? [];

        if (isset($options['systemMessage'])) {
            $messages = array_filter($messages, fn($msg) => $msg['role'] !== 'system');
            $systemMessage = ['role' => 'system', 'content' => $options['systemMessage']];

            $lastUserIndex = -1;
            foreach (array_keys($messages) as $index) {
                if ($messages[$index]['role'] === 'user') {
                    $lastUserIndex = $index;
                }
            }

            if ($lastUserIndex !== -1) {
                array_splice($messages, $lastUserIndex, 0, [$systemMessage]);
            } else {
                $messages[] = $systemMessage;
            }
        }

        $params = [
            'model' => $this->model,
            'messages' => $messages,
            ...($options['tools'] ? ['tools' => $options['tools']] : []),
            ...(isset($options['output_schema']) ? ['response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'response',
                    'schema' => $options['output_schema']
                ]
            ]] : [])
        ];

        if ($isStream) {
            $params['stream'] = true;
            $params['stream_options'] = [
                'include_usage' => true
            ];
        }

        return $params;
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
                'response' => $response->choices[0]->message->content,
                'tokensUsed' => (object) [
                    'input' => $response->usage->prompt_tokens ?? $response->usage->promptTokens,
                    'output' => $response->usage->completion_tokens ?? $response->usage->completionTokens,
                    'total' => $response->usage->total_tokens ?? $response->usage->totalTokens
                ]
            ];
        } elseif ($response->choices[0]->message->tool_calls || $response->choices[0]->message->toolCalls) {
            $toolCalls = $response->choices[0]->message->tool_calls ?? $response->choices[0]->message->toolCalls;
            return [
                'type' => 'tool',
                'rawResponse' => $toolCalls,
                'tools' => $this->processToolForResponse($toolCalls),
                'tokensUsed' => (object) [
                    'input' => $response->usage->prompt_tokens ?? $response->usage->promptTokens,
                    'output' => $response->usage->completion_tokens ?? $response->usage->completionTokens,
                    'total' => $response->usage->total_tokens ?? $response->usage->totalTokens
                ]
            ];
        }

        throw new \Exception('No response from OpenRouter');
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

    private function makeClient(): Client
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->provider->getApiKey(),
            'Content-Type' => 'application/json'
        ];

        if ($this->provider->getAppUrl()) {
            $headers['HTTP-Referer'] = $this->provider->getAppUrl();
        }

        if ($this->provider->getAppName()) {
            $headers['X-Title'] = $this->provider->getAppName();
        }

        return new Client([
            'base_uri' => 'https://openrouter.ai/api/v1',
            'headers' => $headers
        ]);
    }
}
