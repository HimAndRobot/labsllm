<?php

namespace LabsLLM\Providers;

use LabsLLM\Contracts\ProviderInterface;

/**
 * Configuration for the OpenRouter provider
 */
class OpenRouter implements ProviderInterface
{
    public string $name = 'openrouter';
    private string $apiKey;
    private string $model;
    private float $temperature;
    private ?string $appUrl;
    private ?string $appName;

    /**
     * Constructor
     *
     * @param string $apiKey
     * @param string $model
     * @param float $temperature
     * @param string|null $appUrl
     * @param string|null $appName
     */
    public function __construct(
        string $apiKey,
        string $model = 'openai/gpt-4o',
        float $temperature = 0.7,
        ?string $appUrl = null,
        ?string $appName = null
    ) {
        if ($apiKey === null) {
            throw new \InvalidArgumentException('API key is required');
        }

        $this->apiKey = $apiKey;
        $this->model = $model;
        $this->temperature = $temperature;
        $this->appUrl = $appUrl;
        $this->appName = $appName;
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

    public function getAppUrl(): ?string
    {
        return $this->appUrl;
    }

    public function getAppName(): ?string
    {
        return $this->appName;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
