<?php
// @codingStandardsIgnoreFile
declare(strict_types=1);

namespace Ls\Replication\Code;

use \Ls\Omni\Service\Soap\ReplicationOperation;
use Laminas\Code\Generator\GeneratorInterface;

class CronJobConfigGenerator implements GeneratorInterface
{
    /**
     * CronJobConfigGenerator constructor.
     * @param ReplicationOperation $operation
     */

    public function __construct(public ReplicationOperation $operation)
    {
    }

    /**
     * @return string
     */
    public function generate()
    {

        $job_id  = $this->operation->getJobId();
        $job_fqn = $this->operation->getJobFqn();
        return <<<XML
<job name="$job_id" instance="$job_fqn" method="execute">
  <schedule>* * * * *</schedule>
</job>
XML;
    }
}
