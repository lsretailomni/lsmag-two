<?php
declare(strict_types=1);

namespace Ls\Omni\Code;

use CaseHelper\CaseHelperInterface;
use Exception;
use \Ls\Core\Code\AbstractGenerator as CoreGenerator;
use \Ls\Omni\Service\Metadata;
use \Ls\Omni\Service\ServiceType;

abstract class AbstractOmniGenerator extends CoreGenerator
{
    /** @var ServiceType $serviceType */
    public $serviceType;

    /** @var string $baseNamespace */
    public $baseNamespace;

    /** @var CaseHelperInterface $caseHelper */
    public $caseHelper;

    /**
     * @param Metadata $metadata
     * @throws Exception
     */
    public function __construct(public Metadata $metadata)
    {
        // Call the parent constructor to initialize the base generator class.
        parent::__construct();

        // Initialize the properties with values from the provided metadata.
        $this->serviceType = $metadata->getClient()->getServiceType();

        // Build the base namespace based on the service type.
        $this->baseNamespace = $this->fqn('Ls', 'Omni', 'Client', ucfirst($this->serviceType->getValue()));
    }

    /**
     * Returns the service type for the current Omni generator instance.
     *
     * @return ServiceType The service type used in the Omni generator.
     */
    public function getServiceType()
    {
        return $this->serviceType;
    }

    /**
     * Returns the metadata object associated with the Omni generator.
     *
     * @return Metadata The metadata containing service information.
     */
    public function getMetadata()
    {
        return $this->metadata;
    }
}
