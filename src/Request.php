<?php

namespace LabsLLM;

use LabsLLM\Enums\Provider;
use LabsLLM\Config\OpenAIConfig;
use LabsLLM\Config\ConfigInterface;
use LabsLLM\Contracts\ChatInterface;
use LabsLLM\Providers\ProviderInterface;
use LabsLLM\Exceptions\ProviderNotFoundException;

/**
 * Class for initiating LLM requests
 */
class Request
{
    /**
     * Provider to be used
     */
    private ProviderInterface $provider;
    private ?string $prompt = null;
    private ?string $systemMessage = null;
    
    /**
     * Sets the provider to be used
     *
     * @param ProviderInterface $provider
     * @return self
     */
    public function using(ProviderInterface $provider): self
    {
        $this->provider = $provider;
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

    /**
     * Sets the system message
     *
     * @param string $message
     * @return self
     */
    public function withSystemMessage(string $message): self
    {
        $this->systemMessage = $message;
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

        $args = [
            'provider' => $this->provider,
            'systemMessage' => $this->systemMessage,
        ];
        return ChatFactory::create($args);
    }
} 