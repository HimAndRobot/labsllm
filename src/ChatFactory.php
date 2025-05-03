<?php

namespace LabsLLM;

use LabsLLM\Chats\OpenAIChat;
use LabsLLM\Config\ConfigInterface;
use LabsLLM\Config\OpenAIConfig;
use LabsLLM\Contracts\ChatInterface;
use LabsLLM\Exceptions\ProviderNotFoundException;

/**
 * Factory for creating chat instances
 */
class ChatFactory
{
    /**
     * Creates a chat instance based on the provider
     *
     * @param string $provider
     * @param ConfigInterface $config
     * @return ChatInterface
     * @throws ProviderNotFoundException
     */
    public static function create(string $provider, ConfigInterface $config): ChatInterface
    {
        return match ($provider) {
            'openai' => new OpenAIChat($config),
            default => throw new ProviderNotFoundException("Provider '{$provider}' not found or not implemented."),
        };
    }
} 