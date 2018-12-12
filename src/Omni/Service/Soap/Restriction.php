<?php

namespace Ls\Omni\Service\Soap;

class Restriction
{
    /** @var string */
    private $name;
    /** @var RestrictionDefinition[] */
    private $definition;
    /** @var string */
    private $base = null;

    /**
     * SoapEntity constructor.
     *
     * @param string $name
     * @param RestrictionDefinition[] $definition
     * @param string $base
     */
    public function __construct($name, $definition = null, $base = null)
    {
        $this->name = $name;
        $this->base = $base;
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
    protected function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return RestrictionDefinition[]
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * @param RestrictionDefinition[] $definition
     */
    protected function setDefinition($definition)
    {
        $this->definition = $definition;
    }

    /**
     * @return string
     */
    public function getBase()
    {
        return $this->base;
    }

    /**
     * @param string $base
     */
    protected function setBase($base)
    {
        $this->base = $base;
    }
}
