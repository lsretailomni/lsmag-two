<?php
// @codingStandardsIgnoreFile

namespace Ls\Replication\Code;

use \Ls\Omni\Service\Soap\ReplicationOperation;
use Zend\Code\Generator\GeneratorInterface;

/**
 * Class CronJobConfigGenerator
 * @package Ls\Replication\Code
 */
class CronJobConfigGenerator implements GeneratorInterface
{
    /** @var  ReplicationOperation */
    protected $operation;

    /**
     * CronJobConfigGenerator constructor.
     * @param ReplicationOperation $operation
     */

    public function __construct(ReplicationOperation $operation)
    {
        $this->operation = $operation;
    }

    /**
     * @return string
     */
    public function generate()
    {

        $job_id = $this->operation->getJobId();
        $job_fqn = $this->operation->getJobFqn();
        $xml = <<<XML
<job name="$job_id" instance="$job_fqn" method="execute">
  <schedule>* * * * *</schedule>
</job>
XML;

        return $xml;
    }
}
