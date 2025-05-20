<?php
declare(strict_types=1);

namespace Ls\Omni\Service\Soap;

use CaseHelper\CaseHelperFactory;
use CaseHelper\CaseHelperInterface;

/**
 * Represents a SOAP operation including request, response, name, and action.
 */
class Operation
{
    /**
     * @var CaseHelperInterface|null Helper for converting case styles
     */
    public ?CaseHelperInterface $caseHelper = null;

    /**
     * @var string Operation name
     */
    public string $name;

    /**
     * @var Element SOAP request element
     */
    public Element $request;

    /**
     * @var Element|null SOAP response element
     */
    public ?Element $response;

    /**
     * @var string SOAP action URL
     */
    public string $soapAction;

    /**
     * @param string $name
     * @param Element $request
     * @param Element|null $response
     * @param string $soapAction
     * @throws \Exception
     */
    public function __construct(string $name, Element $request, ?Element $response = null, string $soapAction = '')
    {
        $this->name = $name;
        $this->request = $request;
        $this->response = $response;
        $this->soapAction = $soapAction;
        $this->caseHelper = CaseHelperFactory::make(CaseHelperFactory::INPUT_TYPE_PASCAL_CASE);
    }

    /**
     * Get the operation name in SCREAMING_SNAKE_CASE.
     *
     * @return string
     */
    public function getScreamingSnakeName(): string
    {
        return $this->caseHelper->toScreamingSnakeCase($this->name);
    }

    /**
     * Get the operation name, optionally formatted.
     *
     * @param bool $format
     * @return string
     */
    public function getName(bool $format = false): string
    {
        if (!$format) {
            return $this->name;
        }

        return str_replace(' ', '', ucwords(strtolower($this->formatGivenValue($this->name))));
    }

    /**
     * Set the operation name.
     *
     * @param string $name
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get the request element.
     *
     * @return Element
     */
    public function getRequest(): Element
    {
        return $this->request;
    }

    /**
     * Set the request element.
     *
     * @param Element $request
     * @return void
     */
    public function setRequest(Element $request): void
    {
        $this->request = $request;
    }

    /**
     * Get the response element.
     *
     * @return Element|null
     */
    public function getResponse(): ?Element
    {
        return $this->response;
    }

    /**
     * Set the response element.
     *
     * @param Element $response
     * @return void
     */
    public function setResponse(Element $response): void
    {
        $this->response = $response;
    }

    /**
     * Set the SOAP action URL.
     *
     * @param string $soapAction
     * @return void
     */
    public function setSoapAction(string $soapAction): void
    {
        $this->soapAction = $soapAction;
    }

    /**
     * Get the SOAP action URL.
     *
     * @return string
     */
    public function getSoapAction(): string
    {
        return $this->soapAction;
    }

    /**
     * Get formatted value
     *
     * @param string $value
     * @param string $replaceWith
     * @return string
     */
    public function formatGivenValue(string $value, string $replaceWith = ''): string
    {
        return trim(preg_replace('/[\/\[\]()$\-._%&]/', $replaceWith, $value));
    }
}
