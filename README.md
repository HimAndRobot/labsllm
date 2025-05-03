# LLM Wrapper

A simple PHP library that provides a unified interface for interacting with various Large Language Models like OpenAI, Anthropic, and Google.

## Install

```bash
composer require labsllm/llm-wrapper
```

## Basic Usage

Sending a simple prompt to an LLM:

```php
// With prompt only
$response = LabsLLM::text()
    ->using(Provider::OpenAI, 'gpt-4o')
    ->setConfig(new OpenAIConfig('YOUR_API_KEY'))
    ->withPrompt('Your question here')
    ->asText();
```

## With System Instructions

You can include system instructions in your prompt:

```php
// With system instructions
$response = LabsLLM::text()
    ->using(Provider::OpenAI, 'gpt-4o')
    ->setConfig(new OpenAIConfig('YOUR_API_KEY'))
    ->withPrompt('System: Act as a historian\nUser: Tell me about knights')
    ->asText();
``` 