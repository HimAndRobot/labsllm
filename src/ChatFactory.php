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
     * @param array $args
     * @return ChatInterface
     * @throws ProviderNotFoundException
     */
    public static function create(array $args): ChatInterface
    {
        return match ($args['provider']->name) {
            'openai' => new OpenAIChat($args),
            default => throw new ProviderNotFoundException("Provider '{$args['provider']}' not found or not implemented."),
        };
    }
} 