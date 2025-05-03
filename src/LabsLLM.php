<?php

namespace LabsLLM;

use LabsLLM\Enums\Provider;

/**
 * Main class that serves as the entry point for the library
 */
class LabsLLM
{
    /**
     * Library version
     */
    const VERSION = '0.1.0';
    
    /**
     * Starts a text request
     *
     * @return \LabsLLM\Request
     */
    public static function text()
    {
        return new Request();
    }
    
    /**
     * Returns the current version of the library
     *
     * @return string
     */
    public static function getVersion(): string
    {
        return self::VERSION;
    }
} 