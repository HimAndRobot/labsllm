<?php

namespace LabsLLM\Helpers\StreamJson\Scopes;

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