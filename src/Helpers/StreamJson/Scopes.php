<?php

namespace LabsLLM\Helpers\StreamJson;

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

/**
 * Scope for JSON objects
 */
class ObjectScope extends Scope {
    private $object = [];
    private $state = 'key';
    private $keyScope = null;
    private $valueScope = null;
    private $currentKey = null;
    
    public function write($char) {
        if ($this->finished) {
            return false;
        }
        
        if (empty($this->object) && $this->state === 'key' && 
            $this->keyScope === null && $this->valueScope === null) {
            if ($char === '{') return true;
        }
        
        if ($this->state === 'key') {
            if ($this->keyScope === null) {
                if ($this->isWhitespace($char)) {
                    return true;
                } else if ($char === '"') {
                    $this->keyScope = new LiteralScope();
                    return $this->keyScope->write($char);
                } else if ($char === '}') {
                    $this->finished = true;
                    return true;
                } else {
                    return false;
                }
            } else {
                $success = $this->keyScope->write($char);
                
                if ($success) {
                    if ($this->keyScope->isFinished()) {
                        $this->state = 'colon';
                        $this->currentKey = $this->keyScope->getOrAssume();
                    }
                    return true;
                }
                return false;
            }
        } else if ($this->state === 'colon') {
            if ($this->isWhitespace($char)) {
                return true;
            } else if ($char === ':') {
                $this->state = 'value';
                $this->valueScope = null;
                return true;
            } else {
                return false;
            }
        } else if ($this->state === 'value') {
            if ($this->valueScope === null) {
                if ($this->isWhitespace($char)) {
                    return true;
                } else if ($char === '{') {
                    $this->valueScope = new ObjectScope();
                    return $this->valueScope->write($char);
                } else if ($char === '[') {
                    $this->valueScope = new ArrayScope();
                    return $this->valueScope->write($char);
                } else {
                    $this->valueScope = new LiteralScope();
                    return $this->valueScope->write($char);
                }
            } else {
                $success = $this->valueScope->write($char);
                
                if ($success) {
                    if ($this->valueScope->isFinished()) {
                        $this->object[$this->currentKey] = $this->valueScope->getOrAssume();
                        $this->state = 'comma';
                    }
                    return true;
                } else {
                    if ($this->isWhitespace($char)) {
                        return true;
                    } else if ($char === ',') {
                        $this->object[$this->currentKey] = $this->valueScope->getOrAssume();
                        $this->state = 'key';
                        $this->keyScope = null;
                        $this->valueScope = null;
                        return true;
                    } else if ($char === '}') {
                        $this->object[$this->currentKey] = $this->valueScope->getOrAssume();
                        $this->finished = true;
                        return true;
                    } else {
                        return false;
                    }
                }
            }
        } else if ($this->state === 'comma') {
            if ($this->isWhitespace($char)) {
                return true;
            } else if ($char === ',') {
                $this->state = 'key';
                $this->keyScope = null;
                $this->valueScope = null;
                return true;
            } else if ($char === '}') {
                $this->finished = true;
                return true;
            } else {
                return false;
            }
        }
        
        return false;
    }
    
    public function getOrAssume() {
        $assume = $this->object;
        
        if ($this->keyScope !== null || $this->valueScope !== null) {
            if ($this->currentKey && $this->valueScope) {
                $assume[$this->currentKey] = $this->valueScope->getOrAssume();
            }
        }
        
        return $assume;
    }
}

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

/**
 * Scope for literal values (strings, numbers, booleans, null)
 */
class LiteralScope extends Scope {
    private $content = '';
    private $inString = false;
    private $escaped = false;
    
    public function write($char) {
        if ($this->finished) {
            return false;
        }
        
        // Don't append comma to numeric values
        if ($char === ',' && !$this->inString && is_numeric($this->content)) {
            $this->finished = true;
            return false;
        }
        
        $this->content .= $char;
        
        if ($this->content[0] === '"') {
            $this->inString = true;
            
            if (strlen($this->content) >= 2 && $char === '"' && !$this->escaped) {
                $this->finished = true;
            }
            
            if ($char === '\\') {
                $this->escaped = !$this->escaped;
            } else {
                $this->escaped = false;
            }
            
            return true;
        }
        
        if ($this->content === 'true' || $this->content === 'false' || $this->content === 'null') {
            $this->finished = true;
            return true;
        }
        
        if ('true' !== $this->content && strpos('true', $this->content) === 0) return true;
        if ('false' !== $this->content && strpos('false', $this->content) === 0) return true;
        if ('null' !== $this->content && strpos('null', $this->content) === 0) return true;
        
        if (is_numeric($this->content) || $this->content === '-' || preg_match('/^-?\d+(\.\d*)?$/', $this->content)) {
            return true;
        }
        
        return false;
    }
    
    public function getOrAssume() {
        if (strlen($this->content) > 0 && $this->content[0] === '"') {
            if ($this->finished) {
                $decoded = json_decode($this->content, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
            }
            
            $stringContent = substr($this->content, 1);
            if ($this->finished) {
                $stringContent = substr($stringContent, 0, -1);
            }
            return $stringContent;
        }
        
        if ($this->content === 'null') return null;
        if (strpos('null', $this->content) === 0) return null;
        
        if ($this->content === 'true') return true;
        if (strpos('true', $this->content) === 0) return true;
        if ($this->content === 'false') return false;
        if (strpos('false', $this->content) === 0) return false;
        
        if (is_numeric($this->content)) {
            if (strpos($this->content, '.') !== false) {
                return (float)$this->content;
            } else {
                return (int)$this->content;
            }
        }
        
        // Handle potential numeric values with trailing characters
        if (preg_match('/^(-?\d+(\.\d*)?)[^0-9\.].*/s', $this->content, $matches)) {
            if (strpos($matches[1], '.') !== false) {
                return (float)$matches[1];
            } else {
                return (int)$matches[1];
            }
        }
        
        return $this->content;
    }
} 