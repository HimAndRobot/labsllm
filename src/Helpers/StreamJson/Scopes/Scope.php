<?php

namespace LabsLLM\Helpers\StreamJson\Scopes;

/**
 * Base class for all scopes
 */
abstract class Scope {
    protected $finished = false;
    
    /**
     * Writes a character to the scope
     */
    public function write($char) {
        return false;
    }
    
    /**
     * Gets value or assumes value based on partial data
     */
    public function getOrAssume() {
        return null;
    }
    
    /**
     * Checks if the scope is finished
     */
    public function isFinished() {
        return $this->finished;
    }
    
    /**
     * Checks if a character is whitespace
     */
    protected function isWhitespace($char) {
        return $char === ' ' || $char === "\t" || $char === "\n" || $char === "\r";
    }
} 