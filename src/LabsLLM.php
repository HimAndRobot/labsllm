<?php

namespace LabsLLM;

use LabsLLM\TextWrapper;

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
     * @return \LabsLLM\TextWrapper
     */
    public static function text()
    {
        return new TextWrapper();
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