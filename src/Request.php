<?php

namespace LabsLLM;

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
     * @return ChatInterface
     */
    public function fromPrompt(string $prompt): ChatInterface
    {
        return $this->getChat()->send($prompt);
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