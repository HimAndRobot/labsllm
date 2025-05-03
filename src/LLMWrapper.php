<?php

namespace LabsLLM;

/**
 * Main class of the LLMs wrapper
 */
class LLMWrapper
{
    /**
     * Library version
     */
    const VERSION = '0.1.0';
    
    /**
     * Method that returns the current version of the library
     *
     * @return string
     */
    public static function getVersion(): string
    {
        return self::VERSION;
    }
} 