<?php

namespace LabsLLM\Config;

/**
 * Interface for provider configurations
 */
interface ConfigInterface
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
    
    /**
     * Returns the configuration as an array
     *
     * @return array
     */
    public function toArray(): array;
} 