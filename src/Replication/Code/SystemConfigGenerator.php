<?php
// @codingStandardsIgnoreFile

namespace Ls\Replication\Code;

use Ls\Omni\Service\Soap\ReplicationOperation;
use Zend\Code\Generator\GeneratorInterface;

/**
 * Class SystemConfigGenerator
 * @package Ls\Replication\Code
 */
class SystemConfigGenerator implements GeneratorInterface
{
    /** @var  ReplicationOperation */
    protected $operation;

    /**
     * SystemConfigGenerator constructor.
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

        $xml = <<<XML
<field id="{$this->operation->getTableName()}" translate="label" type="text" sortOrder="10" 
       showInDefault="1" showInWebsite="1" showInStore="1">
  <label>{$this->operation->getEntityName()}</label>
</field>
XML;

        return $xml;
    }
}
