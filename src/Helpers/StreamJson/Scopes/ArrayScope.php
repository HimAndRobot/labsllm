<?php

namespace LabsLLM\Helpers\StreamJson\Scopes;

use LabsLLM\Helpers\StreamJson\IncompleteJsonParser;

/**
 * Scope for JSON arrays
 */
class ArrayScope extends Scope {
    private $buffer = '';
    private $items = [];
    private $inString = false;
    private $escaped = false;
    private $depth = 0;
    private $objectDepth = 0;
    private $arrayDepth = 0;
    private $currentItem = '';
    
    public function write($char) {
        if ($this->finished) {
            return false;
        }
        
        $this->buffer .= $char;
        
        // Handle first character
        if (strlen($this->buffer) === 1 && $char === '[') {
            return true;
        }
        
        // Handle string contents
        if ($this->inString) {
            if ($char === '\\') {
                $this->escaped = !$this->escaped;
                return true;
            }
            
            if ($char === '"' && !$this->escaped) {
                $this->inString = false;
            } else {
                $this->escaped = false;
            }
            
            $this->currentItem .= $char;
            return true;
        }
        
        // Handle string start
        if ($char === '"') {
            $this->inString = true;
            $this->currentItem .= $char;
            return true;
        }
        
        // Handle object depth
        if ($char === '{') {
            $this->objectDepth++;
            $this->depth++;
            $this->currentItem .= $char;
            return true;
        }
        
        if ($char === '}') {
            $this->objectDepth--;
            $this->depth--;
            $this->currentItem .= $char;
            return true;
        }
        
        // Handle array depth
        if ($char === '[') {
            $this->arrayDepth++;
            $this->depth++;
            $this->currentItem .= $char;
            return true;
        }
        
        if ($char === ']') {
            if ($this->depth === 0) {
                // End of the main array
                $this->addItemIfNotEmpty();
                $this->finished = true;
                return true;
            }
            
            $this->arrayDepth--;
            $this->depth--;
            $this->currentItem .= $char;
            return true;
        }
        
        // Handle item separators
        if ($char === ',' && $this->depth === 0) {
            $this->addItemIfNotEmpty();
            $this->currentItem = '';
            return true;
        }
        
        // Other characters just add to current item
        if (!$this->isWhitespace($char) || $this->depth > 0 || $this->currentItem !== '') {
            $this->currentItem .= $char;
        }
        
        return true;
    }
    
    private function addItemIfNotEmpty() {
        $trimmed = trim($this->currentItem);
        if ($trimmed !== '') {
            // Try to parse the item
            $parser = new IncompleteJsonParser();
            $parsed = $parser->parse($trimmed);
            $this->items[] = $parsed;
        }
    }
    
    public function getOrAssume() {
        // Process any remaining items
        $this->addItemIfNotEmpty();
        
        return $this->items;
    }
} 