<?php

namespace LabsLLM\Helpers\StreamJson\Scopes;

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