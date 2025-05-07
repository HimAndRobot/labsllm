<?php

namespace LabsLLM\Parameters;

use LabsLLM\Contracts\ParameterInterface;

class ObjectParameter implements ParameterInterface
{
     /**
     * @var string
     */
    protected string $type = 'object';

    /**
     * @var string
     */
    protected string $name;

    /**
     * @var string
     */
    protected string $description;

    /**
     * @var array<ParameterInterface>
     */
    protected array $properties;

    /**
     * @var array<string>
     */
    protected array $required;


    /**
     * @param string $name
     * @param string $description
     * @param array<ParameterInterface> $properties
     */
    public function __construct(string $name, string $description, array $properties, array $required = [])
    {
        $this->name = $name;
        $this->description = $description;
        $this->properties = $properties;
        $this->required = $required;
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
     * @return array<ParameterInterface>
     */
    public function getProperties(): array
    {
        $properties = [];

        foreach ($this->properties ?? [] as $property) {
            $properties[$property->getName()] = $property->mountBody();
        }

        return $properties;
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
            'properties' => $this->getProperties(),
            ...(isset($this->required) ? ['required' => $this->required] : []),
        ];
    }
}