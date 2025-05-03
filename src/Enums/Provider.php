<?php

namespace LabsLLM\Enums;

/**
 * Enum of supported LLM providers
 */
enum Provider: string
{
    case OpenAI = 'openai';
    case Anthropic = 'anthropic';
    case Gemini = 'gemini';
} 