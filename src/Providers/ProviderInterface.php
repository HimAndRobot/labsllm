<?php

namespace LabsLLM\Providers;

/**
 * Interface for provider configurations
 */
interface ProviderInterface
{
    /**
     * Returns the API key
     *
     * @return string
     */
    public function getApiKey(): string;
    
    /**
     * Returns the model to be used
     *
     * @return string
     */
    public function getModel(): string;
} 