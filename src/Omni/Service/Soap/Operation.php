<?php

namespace Ls\Omni\Service\Soap;

use CaseHelper\CaseHelperFactory;
use CaseHelper\CaseHelperInterface;

/**
 * Class Operation
 * @package Ls\Omni\Service\Soap
 */
class Operation
{
    /** @var CaseHelperInterface */
    public $case_helper = null;
    /** @var string */
    public $name;
    /** @var Element */
    public $request;
    /** @var Element */
    public $response;

    /**
     * @param string $name
     * @param Element $request
     * @param Element|null $response
     * @throws \Exception
     */
    public function __construct($name, Element $request, Element $response = null)
    {
        $this->name        = $name;
        $this->request     = $request;
        $this->response    = $response;
        $this->case_helper = CaseHelperFactory::make(CaseHelperFactory::INPUT_TYPE_PASCAL_CASE);
    }

    /**
     * @return string
     */
    public function getScreamingSnakeName()
    {
        return $this->case_helper->toScreamingSnakeCase($this->name);
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
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param Element $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * @return Element
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param Element $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }
}
