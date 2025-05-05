<?php

namespace LabsLLM\Parameters;

use LabsLLM\Contracts\ParameterInterface;

class BooleanParameter implements ParameterInterface
{
     /**
     * @var string
     */
    protected string $type = 'boolean';

    /**
     * @var string
     */
    protected string $name;

    /**
     * @var string
     */
    protected string $description;

    /**
     * @param string $name
     * @param string $description
     * @param array $enum
     */
    public function __construct(string $name, string $description)
    {
        $this->name = $name;
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Summary of mountBody
     * @return array{description: string, type: string}
     */
    public function mountBody(): array
    {
        return [
            'type' => $this->type,
            'description' => $this->description
        ];
    }
}