<?php

namespace LabsLLM;

use LabsLLM\Config\ConfigInterface;
use LabsLLM\Config\OpenAIConfig;
use LabsLLM\Contracts\ChatInterface;
use LabsLLM\Enums\Provider;
use LabsLLM\Exceptions\ProviderNotFoundException;

/**
 * Class for initiating LLM requests
 */
class Request
{
    /**
     * Provider to be used
     */
    private ?string $provider = null;
    
    /**
     * Model to be used
     */
    private ?string $model = null;
    
    /**
     * Prompt to be sent
     */
    private ?string $prompt = null;
    
    /**
     * API key
     */
    private ?string $apiKey = null;

    /**
     * Config
     */
    private ?ConfigInterface $config = null;
    
    /**
     * Sets the provider to be used
     *
     * @param Provider|string $provider
     * @param string|null $model
     * @return self
     */
    public function using(Provider|string $provider, ?string $model = null): self
    {
        if ($provider instanceof Provider) {
            $this->provider = $provider->value;
        } else {
            $this->provider = $provider;
        }
        
        $this->model = $model;
        
        return $this;
    }
    
    /**
     * Sets the prompt to be sent
     *
     * @param string $prompt
     * @return self
     */
    public function withPrompt(string $prompt): self
    {
        $this->prompt = $prompt;
        
        return $this;
    }

    public function setConfig(ConfigInterface $config): self
    {
        $this->config = $config;
        return $this;
    }
    
    /**
     * Gets the response as text
     *
     * @return string
     */
    public function asText(): string
    {
        return $this->getChat()->send($this->prompt)->asText();
    }
    
    /**
     * Gets the appropriate chat based on the provider
     *
     * @return ChatInterface
     * @throws ProviderNotFoundException
     */
    private function getChat(): ChatInterface
    {
        if (!$this->provider) {
            throw new \InvalidArgumentException('Provider not defined');
        }
        
        if (!$this->config) {
            throw new \InvalidArgumentException('Config not defined');
        }

        if ($this->config->getApiKey() === null) {
            throw new \InvalidArgumentException('API key not defined');
        }
        
        return ChatFactory::create($this->provider, $this->config);
    }
    
    /**
     * Creates the appropriate configuration based on the provider
     *
     * @return ConfigInterface
     */
    private function createConfig(): ConfigInterface
    {
        return match ($this->provider) {
            'openai' => new OpenAIConfig(
                $this->apiKey,
                $this->model ?? 'gpt-4o'
            ),
            // Add other providers here when implemented
            default => throw new ProviderNotFoundException("Provider '{$this->provider}' not found or not implemented."),
        };
    }
    
    /**
     * Gets the API key from environment variables
     *
     * @return string|null
     */
    private function getApiKeyFromEnvironment(): ?string
    {
        return match ($this->provider) {
            'openai' => getenv('OPENAI_API_KEY'),
            'anthropic' => getenv('ANTHROPIC_API_KEY'),
            'gemini' => getenv('GEMINI_API_KEY'),
            default => null,
        };
    }
} 