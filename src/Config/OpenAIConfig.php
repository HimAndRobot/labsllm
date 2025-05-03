<?php

namespace LabsLLM\Config;

/**
 * Configuration for the OpenAI provider
 */
class OpenAIConfig implements ConfigInterface
{
    /**
     * API key
     */
    private string $apiKey;
    
    /**
     * Model to be used
     */
    private string $model;
    
    /**
     * Temperature (response creativity)
     */
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
        $this->apiKey = $apiKey;
        $this->model = $model;
        $this->temperature = $temperature;
    }
    
    /**
     * Returns the API key
     *
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }
    
    /**
     * Returns the model to be used
     *
     * @return string
     */
    public function getModel(): string
    {
        return $this->model;
    }
    
    /**
     * Returns the temperature
     *
     * @return float
     */
    public function getTemperature(): float
    {
        return $this->temperature;
    }
    
    /**
     * Returns the configuration as an array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'api_key' => $this->apiKey,
            'model' => $this->model,
            'temperature' => $this->temperature
        ];
    }
} 