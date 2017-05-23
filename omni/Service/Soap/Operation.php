<?php
namespace Ls\Omni\Service\Soap;

use CaseHelper\CaseHelperFactory;
use CaseHelper\CaseHelperInterface;

class Operation
{
    /** @var string */
    private $name;
    /** @var Element */
    private $request;
    /** @var Element */
    private $response;

    /** @var CaseHelperInterface */
    private static $case_helper = NULL;

    /**
     * @param string  $name
     * @param Element $request
     * @param Element $response
     */
    public function __construct ( $name = NULL, Element $request, Element $response = NULL ) {

        $this->name = $name;
        $this->request = $request;
        $this->response = $response;

        if ( is_null( Operation::$case_helper ) ) {
            Operation::$case_helper = CaseHelperFactory::make( CaseHelperFactory::INPUT_TYPE_PASCAL_CASE );
        }
    }

    /**
     * @return string
     */
    public function getScreamingSnakeName () {
        return Operation::$case_helper->toScreamingSnakeCase( $this->name );
    }

    /**
     * @return string
     */
    public function getName () {
        return $this->name;
    }

    /**
     * @param string $name
     */
    protected function setName ( $name ) {
        $this->name = $name;
    }

    /**
     * @return Element
     */
    public function getRequest () {
        return $this->request;
    }

    /**
     * @param Element $request
     */
    protected function setRequest ( $request ) {
        $this->request = $request;
    }

    /**
     * @return Element
     */
    public function getResponse () {
        return $this->response;
    }

    /**
     * @param Element $response
     */
    protected function setResponse ( $response ) {
        $this->response = $response;
    }
}
