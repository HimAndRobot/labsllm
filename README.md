# LLM Wrapper

A simple PHP library that provides a unified interface for interacting with various Large Language Models like OpenAI, Anthropic, and Google.

For complete documentation, visit [labsllm.geanpedro.com.br](https://labsllm.geanpedro.com.br/)

## Install

```bash
composer require labsllm/llm-wrapper
```

## Basic Usage

```php
$execute = LabsLLM::text()
    ->using(new OpenAI('SK-***', 'gpt-4o-mini'))
    ->executePrompt('Your question here');

$response = $execute->getResponseData();

echo $response->response;
```

## Switching Providers

Just change the provider in the `using()` method:

```php
// Using Google
$execute = LabsLLM::text()
    ->using(new Google('API-KEY', 'gemini-pro'))
    ->executePrompt('Your question here');

// Using Anthropic
$execute = LabsLLM::text()
    ->using(new Anthropic('API-KEY', 'claude-3-opus'))
    ->executePrompt('Your question here');
```

## Provider Support Status

Legend:
- ✅ Supported
- 🚧 In Development
- 📅 Planned
- ❌ Not Supported

| Feature | OpenAI | Google | Anthropic |
|---------|--------|-----------|--------|
| Text Prompts | ✅ | ✅ | 📅 |
| System Instructions | ✅ | ✅ | 📅 |
| Chat | ✅ | ✅ | 📅 |
| Tools/Functions | ✅ | 🚧 | ❌ 
| Structure Output | ✅ | 🚧 | ❌ |
| Streaming | ✅ | 🚧 | ❌ |
| Embeddings | 📅 | ❌ | ❌ |
| Voice | 📅 | ❌ | ❌ |
| Image Generation | 📅 | ❌ | 📅 | 