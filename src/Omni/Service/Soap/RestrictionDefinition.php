<?php

namespace Ls\Omni\Service\Soap;

class RestrictionDefinition
{
    /** @var string */
    private $name;
    /** @var string */
    private $value;
    /** @var string */
    private $mapping;

    /**
     * SoapRestriction constructor.
     *
     * @param string $name
     * @param string $value
     * @param string $mapping
     */
    public function __construct($name, $value, $mapping)
    {
        $this->name = $name;
        $this->value = $value;
        $this->mapping = $mapping;
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
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getMapping()
    {
        return $this->mapping;
    }

    /**
     * @param string $mapping
     */
    public function setMapping($mapping)
    {
        $this->mapping = $mapping;
    }
}
