<?php

namespace Ls\Omni\Service\Soap;

/**
 * Class Element
 * @package Ls\Omni\Service\Soap
 */
class Element
{
    /** @var string */
    private $name;

    /** @var string */
    private $type;

    /** @var array */
    private $definition;

    /** @var boolean */
    private $request = false;
    /** @var boolean */
    private $response = false;

    /** @var string */
    private $base = null;

    /**
     * SoapEntity constructor.
     *
     * @param string $name
     * @param string $type
     * @param array $definition
     */
    public function __construct($name, $type, $definition = null)
    {
        $this->name       = $name;
        $this->type       = $type;
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
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
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

    /**
     * @return boolean
     */
    public function isRequest()
    {
        return $this->request;
    }

    /**
     * @param boolean $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * @return boolean
     */
    public function isResponse()
    {
        return $this->response;
    }

    /**
     * @param boolean $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
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
    public function setBase($base)
    {
        $this->base = $base;
    }
}
