<?php

namespace Ls\Omni\Service\Soap;

/**
 * Class Entity
 * @package Ls\Omni\Service\Soap
 */
class Entity
{
    /** @var string */
    private $name;
    /** @var Element */
    private $element;

    /** @var array */
    private $definition;

    /**
     * SoapEntity constructor.
     *
     * @param string $name
     * @param Element $element
     * @param array $definition
     */
    public function __construct($name, $element, $definition)
    {
        $this->name       = $name;
        $this->element    = $element;
        $this->definition = $definition;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return Element
     */
    public function getElement()
    {
        return $this->element;
    }

    /**
     * @param Element $element
     */
    public function setElement($element)
    {
        $this->element = $element;
    }

    /**
     * @return array
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * @param array $definition
     */
    public function setDefinition($definition)
    {
        $this->definition = $definition;
    }
}
