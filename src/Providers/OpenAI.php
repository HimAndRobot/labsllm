<?php

namespace LabsLLM\Providers;

use LabsLLM\Contracts\ProviderInterface;

/**
 * Configuration for the OpenAI provider
 */
class OpenAI implements ProviderInterface
{
    public string $name = 'openai';
    private string $apiKey;
    private string $model;
    private float $temperature;

    /**
     * Constructor
     *
     * @param string $apiKey
     * @param string $model
     * @param float $temperature
     */
    public function __construct(string $apiKey, string $model = 'gpt-4o', float $temperature = 0.7)
    {
        if ($apiKey === null) {
            throw new \InvalidArgumentException('API key is required');
        }

        $this->apiKey = $apiKey;
        $this->model = $model;
        $this->temperature = $temperature;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getTemperature(): float
    {
        return $this->temperature;
    }

    public function getName(): string
    {
        return $this->name;
    }
} 
