<?php
// @codingStandardsIgnoreFile

namespace Ls\Replication\Code;

use DOMDocument;
use Laminas\Code\Generator\GeneratorInterface;
use \Ls\Omni\Service\Metadata;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Module\Dir\Reader;

/**
 * Class SchemaUpdateGenerator
 * @package Ls\Replication\Code
 */
class SchemaUpdateGenerator implements GeneratorInterface
{

    /** @var Metadata */
    protected $metadata;

    /**
     * SchemaUpdateGenerator constructor.
     * @param Metadata $metadata
     */
    public function __construct(Metadata $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * Create dynamic db_schema.xml file and save to etc folder of Replication Module
     */
    public function generate()
    {
        $dom               = new DOMDocument('1.0');
        $dom->formatOutput = true;
        $schema            = $dom->createElement('schema');
        $schema->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $schema->setAttribute('xsi:noNamespaceSchemaLocation', 'urn:magento:framework:Setup/Declaration/Schema/etc/schema.xsd');
        $tables = [];
        foreach ($this->metadata->getOperations() as $operationName => $operation) {
            if (strpos($operationName, 'ReplEcomm') !== false) {
                $replicationOperation = $this->metadata->getReplicationOperationByName($operation->getName());
                $tableName            = "ls_replication_" . $replicationOperation->getTableName();
                if (!in_array($tableName, $tables)) {
                    $table = $dom->createElement('table');
                    $table->setAttribute('name', $tableName);
                    $table->setAttribute('resource', 'default');
                    $table->setAttribute('engine', 'innodb');
                    $table->setAttribute('comment', $replicationOperation->getName());
                    $column = $dom->createElement('column');
                    $column->setAttribute('name', $replicationOperation->getTableColumnId());
                    $column->setAttribute('xsi:type', 'int');
                    $column->setAttribute('padding', '10');
                    $column->setAttribute('unsigned', 'false');
                    $column->setAttribute('nullable', 'false');
                    $column->setAttribute('identity', 'true');
                    $column->setAttribute('comment', $replicationOperation->getTableColumnId());
                    $table->appendChild($column);
                    $extraColumnsArray = [
                        [
                            'name'       => 'processed',
                            'field_type' => 'boolean',
                            'default'    => '0',
                            'comment'    => 'Flag to check if data is already copied into Magento. 0 means needs to be copied into Magento tables & 1 means already copied'
                        ],
                        [
                            'name'       => 'is_updated',
                            'field_type' => 'boolean',
                            'default'    => '0',
                            'comment'    => 'Flag to check if data is already updated from Omni into Magento. 0 means already updated & 1 means needs to be updated into Magento tables'
                        ],
                        [
                            'name'       => 'is_failed',
                            'field_type' => 'boolean',
                            'default'    => '0',
                            'comment'    => 'Flag to check if data is already added from Flat into Magento successfully or not. 0 means already added successfully & 1 means failed to add successfully into Magento tables'
                        ],
                        [
                            'name'       => 'checksum',
                            'field_type' => 'text',
                            'default'    => '',
                            'comment'    => 'Checksum'
                        ],
                        [
                            'name'       => 'processed_at',
                            'field_type' => 'timestamp',
                            'default'    => '',
                            'comment'    => 'Processed At'
                        ],
                        [
                            'name'       => 'created_at',
                            'field_type' => 'timestamp',
                            'default'    => 'CURRENT_TIMESTAMP',
                            'comment'    => 'Created At'
                        ],
                        [
                            'name'       => 'updated_at',
                            'field_type' => 'timestamp',
                            'default'    => 'CURRENT_TIMESTAMP',
                            'comment'    => 'Updated At'
                        ]
                    ];
                    foreach ($extraColumnsArray as $columnValue) {
                        $extraColumn = $dom->createElement('column');
                        $extraColumn->setAttribute('name', $columnValue['name']);
                        $extraColumn->setAttribute('xsi:type', $columnValue['field_type']);
                        if ($columnValue['default'] != '')
                            $extraColumn->setAttribute('default', $columnValue['default']);
                        $extraColumn->setAttribute('nullable', 'true');
                        $extraColumn->setAttribute('comment', $columnValue['comment']);
                        $table->appendChild($extraColumn);
                    }
                    $constraint = $dom->createElement('constraint');
                    $constraint->setAttribute('xsi:type', 'primary');
                    $constraint->setAttribute('referenceId', 'PRIMARY');
                    $column = $dom->createElement('column');
                    $column->setAttribute('name', $replicationOperation->getTableColumnId());
                    $constraint->appendChild($column);
                    $table->appendChild($constraint);
                    $schema->appendChild($table);
                    array_push($tables, $tableName);
                }
            }
        }
        $dom->appendChild($schema);
        $dom->save($this->getPath());
    }

    /**
     * @return string
     */
    public function getPath()
    {
        $objectManager = ObjectManager::getInstance();
        /** @var  Reader $dirReader */
        $dirReader = $objectManager->get('\Magento\Framework\Module\Dir\Reader');
        $basePath  = $dirReader->getModuleDir('', 'Ls_Replication');
        return $basePath . "/etc/db_schema_new.xml";
    }
}
