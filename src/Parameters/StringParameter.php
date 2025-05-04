<?php

namespace LabsLLM\Parameters;

use LabsLLM\Contracts\ParameterInterface;

class StringParameter implements ParameterInterface
{
    protected string $type = 'string';

    protected string $name;

    protected string $description;

    protected array $enum;


    public function __construct(string $name, string $description, array $enum = [])
    {
        $this->name = $name;
        $this->description = $description;
        $this->enum = $enum;
    }


    public function getName(): string
    {
        return $this->name;
    }

    public function mountBody(): array
    {
        return [
            'type' => $this->type,
            'description' => $this->description,
            ...(count($this->enum) > 0 ? ['enum' => $this->enum] : []),
        ];
    }
}