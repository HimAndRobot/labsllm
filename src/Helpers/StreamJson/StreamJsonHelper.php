<?php

namespace LabsLLM\Helpers\StreamJson;

require_once __DIR__ . '/IncompleteJsonParser.php';

/**
 * StreamJsonHelper class for processing incomplete or partial JSON.
 */
class StreamJsonHelper {
    /**
     * Repairs an incomplete JSON and returns an associative array
     * 
     * @param string $partialJson Incomplete JSON
     * @return array Array representing the repaired JSON
     */
    public static function getPartialJsonValue($partialJson) {
        if (empty(trim($partialJson))) {
            return [];
        }
        
        $decoded = json_decode($partialJson, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
        
        $parser = new IncompleteJsonParser();
        return $parser->parse($partialJson);
    }
} 