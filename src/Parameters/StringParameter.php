<?php

namespace LabsLLM\Parameters;

use LabsLLM\Contracts\ParameterInterface;

class StringParameter implements ParameterInterface
{
     /**
     * @var string
     */
    protected string $type = 'string';

    /**
     * @var string
     */
    protected string $name;

    /**
     * @var string
     */
    protected string $description;

    /**
     * @var array
     */
    protected array $enum;


    /**
     * @param string $name
     * @param string $description
     * @param array $enum
     */
    public function __construct(string $name, string $description, array $enum = [])
    {
        $this->name = $name;
        $this->description = $description;
        $this->enum = $enum;
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
            'description' => $this->description,
            ...(count($this->enum) > 0 ? ['enum' => $this->enum] : []),
        ];
    }
}