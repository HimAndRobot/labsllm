<?php

// Carrega o autoloader do Composer
require_once __DIR__ . '/vendor/autoload.php';

// Importa a classe principal
use LabsLLM\LLMWrapper;

// Exibe a versão da biblioteca
echo "Versão da biblioteca: " . LLMWrapper::getVersion() . PHP_EOL;
echo "Biblioteca instalada com sucesso!" . PHP_EOL; 