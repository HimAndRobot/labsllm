<?php

namespace LabsLLM\Helpers\StreamJson;

require_once __DIR__ . '/Scopes.php';

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
        
        for ($i = 0; $i < strlen($chunk); $i++) {
            $char = $chunk[$i];
            
            if ($this->finished) {
                if ($this->isWhitespace($char)) continue;
                break;
            }
            
            if ($this->scope === null) {
                if ($this->isWhitespace($char)) continue;
                else if ($char === '{') $this->scope = new ObjectScope();
                else if ($char === '[') $this->scope = new ArrayScope();
                else $this->scope = new LiteralScope();
                $this->scope->write($char);
            } else {
                $success = $this->scope->write($char);
                if ($success) {
                    if ($this->scope->isFinished()) {
                        $this->finished = true;
                        continue;
                    }
                }
            }
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