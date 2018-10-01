<?php

namespace Ls\Omni\Service\Soap;

use CaseHelper\CaseHelperFactory;
use CaseHelper\CaseHelperInterface;

class Operation
{
    /** @var CaseHelperInterface */
    protected $case_helper = NULL;
    /** @var string */
    protected $name;
    /** @var Element */
    protected $request;
    /** @var Element */
    protected $response;

    /**
     * @param string $name
     * @param Element $request
     * @param Element $response
     */
    public function __construct($name = NULL, Element $request, Element $response = NULL)
    {

        $this->name = $name;
        $this->request = $request;
        $this->response = $response;
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
    protected function setName($name)
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
    protected function setRequest($request)
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
    protected function setResponse($response)
    {
        $this->response = $response;
    }
}
