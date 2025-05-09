<?php

namespace LabsLLM\Chats;

use LabsLLM\Contracts\ChatInterface;
use LabsLLM\Contracts\ProviderInterface;

/**
 * Base class for all chats
 */
abstract class BaseChat implements ChatInterface
{
    /**
     * Chat model
     */
    protected string $model;

    /**
     * Chat API key
     */
    protected string $apiKey;

    public function __construct(ProviderInterface $provider)
    {   
        $this->model = $provider->getModel();
        $this->apiKey = $provider->getApiKey();
    }
} 