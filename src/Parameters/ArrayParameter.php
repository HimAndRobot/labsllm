<?php

namespace LabsLLM\Parameters;

use LabsLLM\Contracts\ParameterInterface;

class ArrayParameter implements ParameterInterface
{
     /**
     * @var string
     */
    protected string $type = 'array';

    /**
     * @var string
     */
    protected string $name;

    /**
     * @var string
     */
    protected string $description;

    /**
     * @var ParameterInterface
     */
    protected ParameterInterface $items;


    /**
     * @param string $name
     * @param string $description
     * @param ParameterInterface $items
     */
    public function __construct(string $name, string $description, ParameterInterface $items)
    {
        $this->name = $name;
        $this->description = $description;
        $this->items = $items;
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
            'items' => [
                'type' => $this->items->getType(),
            ]
        ];
    }
}