<?php

namespace LabsLLM\Helpers;

class JsonRepairer {
    /**
     * Repara um JSON incompleto e retorna um array associativo
     * 
     * @param string $partialJson JSON incompleto
     * @return array Array associativo representando o JSON reparado
     */
    public static function repair($partialJson) {
        // Se estiver vazio, retornar array vazio
        if (empty(trim($partialJson))) {
            return [];
        }
        
        // Primeiro tenta decodificar diretamente (JSON completo válido)
        $decoded = json_decode($partialJson, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
        
        // Inicializa o parser
        $parser = new IncompleteJsonParser();
        return $parser->parse($partialJson);
    }
}

/**
 * Parser para JSON incompleto, inspirado na implementação JavaScript
 */
class IncompleteJsonParser {
    private $scope = null;
    private $finished = false;
    
    /**
     * Analisa uma string JSON incompleta
     */
    public function parse($chunk) {
        $this->reset();
        
        for ($i = 0; $i < strlen($chunk); $i++) {
            $char = $chunk[$i];
            
            if ($this->finished) {
                if ($this->isWhitespace($char)) continue;
                break; // Parser já terminou
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
     * Reseta o parser para um novo uso
     */
    public function reset() {
        $this->scope = null;
        $this->finished = false;
    }
    
    /**
     * Obtém os objetos parseados
     */
    public function getObjects() {
        if ($this->scope) {
            return $this->scope->getOrAssume();
        }
        return null;
    }
    
    /**
     * Verifica se um caractere é espaço em branco
     */
    private function isWhitespace($char) {
        return $char === ' ' || $char === "\t" || $char === "\n" || $char === "\r";
    }
}

/**
 * Classe base para todos os escopos
 */
abstract class Scope {
    protected $finished = false;
    
    /**
     * Escreve um caractere no escopo
     */
    public function write($char) {
        return false;
    }
    
    /**
     * Obtém o valor ou assume valor baseado nos dados parciais
     */
    public function getOrAssume() {
        return null;
    }
    
    /**
     * Verifica se o escopo foi finalizado
     */
    public function isFinished() {
        return $this->finished;
    }
    
    /**
     * Verifica se um caractere é espaço em branco
     */
    protected function isWhitespace($char) {
        return $char === ' ' || $char === "\t" || $char === "\n" || $char === "\r";
    }
}

/**
 * Escopo para objetos JSON
 */
class ObjectScope extends Scope {
    private $object = [];
    private $state = 'key'; // 'key', 'colon', 'value', 'comma'
    private $keyScope = null;
    private $valueScope = null;
    
    public function write($char) {
        if ($this->finished) {
            return false; // Objeto já finalizado
        }
        
        // Ignora o primeiro '{'
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
                $key = $this->keyScope->getOrAssume();
                
                if (is_string($key)) {
                    if ($this->keyScope->isFinished()) {
                        $this->state = 'colon';
                    }
                    return true;
                } else {
                    return false;
                }
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
                
                if ($this->valueScope->isFinished()) {
                    $key = $this->keyScope->getOrAssume();
                    $this->object[$key] = $this->valueScope->getOrAssume();
                    $this->state = 'comma';
                    return true;
                } else if ($success) {
                    return true;
                } else {
                    if ($this->isWhitespace($char)) {
                        return true;
                    } else if ($char === ',') {
                        $key = $this->keyScope->getOrAssume();
                        $this->object[$key] = $this->valueScope->getOrAssume();
                        $this->state = 'key';
                        $this->keyScope = null;
                        $this->valueScope = null;
                        return true;
                    } else if ($char === '}') {
                        $key = $this->keyScope->getOrAssume();
                        $this->object[$key] = $this->valueScope->getOrAssume();
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
            $key = $this->keyScope ? $this->keyScope->getOrAssume() : null;
            $value = $this->valueScope ? $this->valueScope->getOrAssume() : null;
            
            if (is_string($key) && !empty($key)) {
                if ($value !== null) {
                    $assume[$key] = $value;
                } else {
                    $assume[$key] = null;
                }
            }
        }
        
        return $assume;
    }
}

/**
 * Escopo para arrays JSON
 */
class ArrayScope extends Scope {
    private $array = [];
    private $state = 'value'; // 'value' ou 'comma'
    private $currentScope = null;
    
    public function write($char) {
        if ($this->finished) {
            return false; // Array já finalizado
        }
        
        // Ignora o primeiro '['
        if (empty($this->array) && $this->state === 'value' && $this->currentScope === null) {
            if ($char === '[') return true;
        }
        
        if ($this->state === 'value') {
            if ($this->currentScope === null) {
                if ($this->isWhitespace($char)) {
                    return true;
                } else if ($char === ']') {
                    $this->finished = true;
                    return true;
                } else if ($char === '{') {
                    $this->currentScope = new ObjectScope();
                    $this->array[] = $this->currentScope;
                    return $this->currentScope->write($char);
                } else if ($char === '[') {
                    $this->currentScope = new ArrayScope();
                    $this->array[] = $this->currentScope;
                    return $this->currentScope->write($char);
                } else {
                    $this->currentScope = new LiteralScope();
                    $this->array[] = $this->currentScope;
                    return $this->currentScope->write($char);
                }
            } else {
                $success = $this->currentScope->write($char);
                
                if ($success) {
                    if ($this->currentScope->isFinished()) {
                        $this->state = 'comma';
                    }
                    return true;
                } else {
                    if ($this->isWhitespace($char)) {
                        return true;
                    } else if ($char === ',') {
                        $this->state = 'value';
                        $this->currentScope = null;
                        return true;
                    } else if ($char === ']') {
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
                $this->state = 'value';
                $this->currentScope = null;
                return true;
            } else if ($char === ']') {
                $this->finished = true;
                return true;
            } else {
                return false;
            }
        }
        
        return false;
    }
    
    public function getOrAssume() {
        $result = [];
        
        foreach ($this->array as $scope) {
            $result[] = $scope->getOrAssume();
        }
        
        return $result;
    }
}

/**
 * Escopo para valores literais (strings, números, booleanos, null)
 */
class LiteralScope extends Scope {
    private $content = '';
    private $inString = false;
    private $escaped = false;
    
    public function write($char) {
        if ($this->finished) {
            return false;
        }
        
        $this->content .= $char;
        
        // Strings
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
        
        // true, false, null
        if ($this->content === 'true' || $this->content === 'false' || $this->content === 'null') {
            $this->finished = true;
            return true;
        }
        
        // Verificação parcial para true, false, null
        if ('true' !== $this->content && strpos('true', $this->content) === 0) return true;
        if ('false' !== $this->content && strpos('false', $this->content) === 0) return true;
        if ('null' !== $this->content && strpos('null', $this->content) === 0) return true;
        
        // Números
        if (is_numeric($this->content) || $this->content === '-' || preg_match('/^-?\d+(\.\d*)?$/', $this->content)) {
            return true;
        }
        
        // Não conseguiu identificar o valor
        return false;
    }
    
    public function getOrAssume() {
        // String
        if (strlen($this->content) > 0 && $this->content[0] === '"') {
            if ($this->finished) {
                // Tenta decodificar a string JSON
                $decoded = json_decode($this->content, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
            }
            
            // Retorna o conteúdo sem as aspas
            $stringContent = substr($this->content, 1);
            if ($this->finished) {
                $stringContent = substr($stringContent, 0, -1);
            }
            return $stringContent;
        }
        
        // null
        if ($this->content === 'null') return null;
        if (strpos('null', $this->content) === 0) return null;
        
        // Boolean
        if ($this->content === 'true') return true;
        if (strpos('true', $this->content) === 0) return true;
        if ($this->content === 'false') return false;
        if (strpos('false', $this->content) === 0) return false;
        
        // Número
        if (is_numeric($this->content)) {
            if (strpos($this->content, '.') !== false) {
                return (float)$this->content;
            } else {
                return (int)$this->content;
            }
        }
        
        // Não conseguiu identificar, retorna o conteúdo como string
        return $this->content;
    }
} 