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
    ->using(new OpenAI($apiKey, 'gpt-4o-mini'))
    ->withPrompt('Your question here')
    ->asText();
```

## With System Instructions

You can include system instructions in your prompt:

```php
// With system instructions
$response = LabsLLM::text()
    ->using(new OpenAI($apiKey, 'gpt-4o-mini'))
    ->withPrompt('System: Act as a historian\nUser: Tell me about knights')
    ->asText();
```

## Provider Support Status

Legend:
- ✅ Supported
- 🚧 In Development
- 📅 Planned
- ❌ Not Supported

| Feature | OpenAI | Google | Anthropic |
|---------|--------|-----------|--------|
| Text Prompts | ✅ | 🚧 | 📅 |
| System Instructions | ✅ | 🚧 | 📅 |
| Tools/Functions | 🚧 | 📅 | ❌ |
| Embeddings | 📅 | ❌ | ❌ |
| Voice | 📅 | ❌ | ❌ |
| Image Generation | 📅 | ❌ | 📅 | 