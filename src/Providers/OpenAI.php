<?php

namespace LabsLLM\Providers;

/**
 * Configuration for the OpenAI provider
 */
class OpenAI extends BaseProvider
{
    public string $name = 'openai';
    /**
     * Constructor
     *
     * @param string $apiKey
     * @param string $model
     * @param float $temperature
     */
    public function __construct(string $apiKey, string $model = 'gpt-4o', float $temperature = 0.7)
    {
        if (empty($apiKey)) {
            throw new \InvalidArgumentException('API key is required');
        }

        parent::__construct($apiKey, $model, $temperature);
    }
} 