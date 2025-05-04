<?php

namespace LabsLLM\Providers;

abstract class BaseProvider implements ProviderInterface
{
    protected string $apiKey;
    protected string $model;
    protected float $temperature;

    public function __construct(string $apiKey, string $model, float $temperature)
    {
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
}
