<?php

namespace LabsLLM\Helpers\StreamJson;

use LabsLLM\Helpers\StreamJson\Scopes\Scope;
use LabsLLM\Helpers\StreamJson\Scopes\ObjectScope;
use LabsLLM\Helpers\StreamJson\Scopes\ArrayScope;
use LabsLLM\Helpers\StreamJson\Scopes\LiteralScope;

/**
 * Parser for incomplete JSON
 */
class IncompleteJsonParser {
    private $scope = null;
    private $finished = false;
    
    /**
     * Parses an incomplete JSON string
     */
    public function parse($chunk) {
        $this->reset();
        
        // First try standard JSON decode
        $decoded = json_decode($chunk, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
        
        // Try to repair and parse the incomplete JSON
        $trimmed = trim($chunk);
        
        if (empty($trimmed)) {
            return null;
        }
        
        for ($i = 0; $i < strlen($trimmed); $i++) {
            $char = $trimmed[$i];
            
            if ($this->scope === null) {
                if ($this->isWhitespace($char)) continue;
                else if ($char === '{') $this->scope = new ObjectScope();
                else if ($char === '[') $this->scope = new ArrayScope();
                else $this->scope = new LiteralScope();
            }
            
            $this->scope->write($char);
        }
        
        return $this->getObjects();
    }
    
    /**
     * Resets the parser for new use
     */
    public function reset() {
        $this->scope = null;
        $this->finished = false;
    }
    
    /**
     * Gets the parsed objects
     */
    public function getObjects() {
        if ($this->scope) {
            return $this->scope->getOrAssume();
        }
        return null;
    }
    
    /**
     * Checks if a character is whitespace
     */
    private function isWhitespace($char) {
        return $char === ' ' || $char === "\t" || $char === "\n" || $char === "\r";
    }
} 