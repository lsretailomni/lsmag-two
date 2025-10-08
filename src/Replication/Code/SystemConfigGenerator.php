<?php
// @codingStandardsIgnoreFile
declare(strict_types=1);

namespace Ls\Replication\Code;

use \Ls\Omni\Service\Soap\ReplicationOperation;
use Laminas\Code\Generator\GeneratorInterface;

class SystemConfigGenerator implements GeneratorInterface
{
    /**
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

        $xml = <<<XML
<field id="{$this->operation->getTableName()}" translate="label" type="text" sortOrder="10"
       showInDefault="1" showInWebsite="1" showInStore="1">
  <label>{$this->operation->getEntityName()}</label>
</field>
XML;

        return $xml;
    }
}
