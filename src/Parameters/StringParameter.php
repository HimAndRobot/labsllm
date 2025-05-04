<?php

namespace LabsLLM\Parameters;

use LabsLLM\Contracts\ParameterInterface;

class StringParameter implements ParameterInterface
{
    protected string $type = 'string';

    protected string $name;

    protected string $description;


    public function __construct(string $name, string $description)
    {
        $this->name = $name;
        $this->description = $description;
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
        ];
    }
}