<?php

namespace Ls\Omni\Code;

use CaseHelper\CaseHelperInterface;
use Ls\Core\Code\AbstractGenerator as CoreGenerator;
use Ls\Omni\Service\Metadata;
use Ls\Omni\Service\ServiceType;

/**
 * Class AbstractOmniGenerator
 * @package Ls\Omni\Code
 */
abstract class AbstractOmniGenerator extends CoreGenerator
{
    /** @var ServiceType */
    protected $service_type;

    /** @var Metadata */
    protected $metadata;

    /** @var string */
    protected $base_namespace;

    /** @var CaseHelperInterface */
    protected $case_helper;

    /**
     * AbstractOmniGenerator constructor.
     * @param Metadata $metadata
     * @throws \Exception
     */
    public function __construct(Metadata $metadata)
    {
        parent::__construct();

        $this->metadata = $metadata;
        $this->service_type = $metadata->getClient()->getServiceType();
        $this->base_namespace = $this->fqn('Ls', 'Omni', 'Client', ucfirst($this->service_type->getValue()));
    }

    /**
     * @return ServiceType
     */
    public function getServiceType()
    {
        return $this->service_type;
    }

    /**
     * @return Metadata
     */
    public function getMetadata()
    {
        return $this->metadata;
    }
}
