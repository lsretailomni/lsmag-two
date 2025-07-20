<?php
declare(strict_types=1);

namespace Ls\Omni\Code;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Code\AbstractGenerator;
use \Ls\Omni\Helper\Data;
use Magento\Framework\Exception\NoSuchEntityException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class OdataGenerator
{
    /**
     * @var array
     */
    public array $replication = [];

    /**
     * @var array
     */
    public array $nonReplication = [];

    /**
     * @var array
     */
    public array $entities = [];

    /**
     * @var array[]
     */
    public array $allowedNonReplActions = [
        'GetStores_GetStores' => [
          'request' => [
              'storeGetType' => '3',
              'searchText' => 'S0001',
              'includeDetail' => true
          ],
          'response' => [
              'DataSetName' => ''
          ]
        ],
        'TestConnectionOData_TestConnection' => [
            'request' => [],
            'response' => [
                'indexName' => 'TestConnectionResponse'
            ]
        ],
        'GetMemberContactInfo_GetMemberContactInfo' => [
            'request' => [
                'contactSearchType'=> '3',
                'searchText'=> 'tom@pzwqk.kgh',
                'searchMethod'=> '0',
                'maxResultContacts'=> 0
            ],
            'response' => [
                'DataSetName' => ''
            ]
        ],
        'GetMemContSalesHist_GetMemContSalesHist' => [
            'request' => [
                'memberCardNo'=> '10044',
                'storeNo'=> '',
                'dateFilter'=> "1990-01-01",
                'dateGreaterThan'=> true,
                'maxResultContacts'=> 0
            ],
            'response' => [
                'DataSetName' => ''
            ]
        ],
        'GetDiscount_GetDiscount' => [
            'request' => [
                'storeNo'=> 'S0013',
                'schemeCode'=> 'CR1-BRONZE',
                'items' => '40000'
            ],
            'response' => [
                'DataSetName' => ''
            ]
        ],
        'GetItem_GetItem' => [
            'request' => [
                'storeNo'=> 'S0013',
                'itemNo' => '40180',
                'barcode' => '',
                'includeDetail' => true
            ],
            'response' => [
                'DataSetName' => ''
            ]
        ],
        'GetSelectedSalesDoc_GetSelectedSalesDoc' => [
            'request' => [
                'documentSourceType'=> '1',
                'documentID' => 'CO000001'
            ],
            'response' => [
                'DataSetName' => ''
            ]
        ],
        'GetSalesInfoByOrderId_GetSalesInfoByOrderId' => [
            'request' => [
                'customerOrderId'=> 'CO000001',
            ],
            'response' => [
                'DataSetName' => ''
            ]
        ],
        'GetImage_GetImage' => [
            'request' => [
                'imageNo' => '40000',
                'mediaId' => ''
            ],
            'response' => [
                'DataSetName' => ''
            ]
        ]
    ];

    /**
     * Generate odata classes
     *
     * @param string $entityDir
     * @param string $operationDir
     * @param Data $omniDataHelper
     * @param OutputInterface $output
     * @return array
     * @throws GuzzleException
     * @throws NoSuchEntityException
     */
    public function generate(
        string $entityDir,
        string $operationDir,
        Data $omniDataHelper,
        OutputInterface $output
    ) {
        $storeCode          = $omniDataHelper->lsr->getActiveWebStore();
        $baseNamespace      = AbstractGenerator::fqn('Ls', 'Omni', 'Client', 'Ecommerce', 'Entity');
        $operationNamespace = AbstractGenerator::fqn('Ls', 'Omni', 'Client', 'Ecommerce', 'Operation');
        $this->generateBaseRequestContent($entityDir, $baseNamespace, $output);
        $this->generateBaseResponseContent($entityDir, $baseNamespace, $output);
        $this->segregateWebservices($omniDataHelper);
        $nonReplicationServices = $this->getNonReplicationServices();
        $replicationServices = $this->getReplicationServices();

        foreach ($replicationServices as $replication) {
            $hasStoreNo = $replication['hasStoreNo'] ?? null;
            $requestClassName = $replication['requestClassName'] ?? null;

            if ($hasStoreNo !== null && $requestClassName) {
                $this->generateRequestContent($entityDir, $baseNamespace, $output, $requestClassName, $hasStoreNo);
                list($recRef, $recordFields) = $this->fetchGivenReplicationWebserviceFieldsAndRecords(
                    $omniDataHelper,
                    $requestClassName,
                    $hasStoreNo,
                    $storeCode
                );

                $this->generateGivenReplicationWebserviceEntityOperationResponseCode(
                    $baseNamespace,
                    $operationNamespace,
                    $entityDir,
                    $operationDir,
                    $requestClassName,
                    $output,
                    $recRef,
                    $recordFields
                );
            }
        }

        foreach ($nonReplicationServices as $nonReplication) {
            $requestClassName = $nonReplication['requestClassName'] ?? null;
            $params = $nonReplication['params'] ?? null;

            if ($requestClassName && $params !== null) {
                if (isset($this->allowedNonReplActions[$requestClassName])) {
                    $action = $requestClassName;
                    $requestClassName .= 'Request';
                    $this->generateCustomRequest(
                        $entityDir,
                        $baseNamespace,
                        $output,
                        $requestClassName,
                        $action,
                        $params
                    );
                    
                    if ($requestClassName == "GetImage_GetImage") {
                        $flag = 1;
                    }

                    $data = $omniDataHelper->fetchGivenOdata(
                        $action,
                        '',
                        [],
                        [],
                        $this->allowedNonReplActions[$action]['request'],
                    );
                    $dataSetName = $this->allowedNonReplActions[$action]['response']['DataSetName'] ??
                        $this->allowedNonReplActions[$action]['response']['indexName'];

                    $recordFields = $this->findDataSetFieldsRecursive($data, $dataSetName);
                    if ($dataSetName == "" && !empty($recordFields)) {
                        $this->generateGivenNonReplicationWebserviceEntityOperationResponseCode(
                            $baseNamespace,
                            $operationNamespace,
                            $entityDir,
                            $operationDir,
                            $requestClassName,
                            $output,
                            $recordFields,
                            $action
                        );
                    } else {
                        if (!empty($recordFields)) {
                            $this->generateGivenNonReplicationWebserviceEntityOperationResponseCodeGivenDataSetName(
                                $baseNamespace,
                                $operationNamespace,
                                $entityDir,
                                $operationDir,
                                $requestClassName,
                                $output,
                                $recordFields,
                                $dataSetName
                            );
                        }
                    }
                }
            }
        }
        $classMap = [];

        foreach ($this->entities as $entityClassName => $entity) {
            $this->generateEntityContent(
                $entityDir,
                $baseNamespace,
                $output,
                $entity['sanitizedClassName'],
                $entity['RecordFields'],
                $entity['recursive']
            );

            $classMap[$entityClassName] = $entity['sanitizedClassName'];
        }

        return $classMap;
    }

    /**
     * Generate code for given non-replication webservice entity, operation and response code
     *
     * When dataset name is not defined
     *
     * @param string $baseNamespace
     * @param string $operationNamespace
     * @param string $entityDir
     * @param string $operationDir
     * @param string $requestClassName
     * @param OutputInterface $output
     * @param array $recordFields
     * @param string $action
     * @return void
     */
    public function generateGivenNonReplicationWebserviceEntityOperationResponseCode(
        string $baseNamespace,
        string $operationNamespace,
        string $entityDir,
        string $operationDir,
        string $requestClassName,
        OutputInterface $output,
        array $recordFields,
        string $action
    ) {
        $dataSetNames = $dataSetName = [];
        foreach ($recordFields as $recordField) {
            if (isset($recordField['DataSetName'])) {
                if ($recordField['DataSetName'] == 'LSC Rtl Calendar Group Linking') {
                    $i = 1;
                }
                $entityClassName = $this->formatGivenValue(str_replace(
                    ' ',
                    '',
                    $recordField['DataSetName']
                ));
                $dataSetNames[] = [
                    'FieldName' => $recordField['DataSetName'],
                    'FieldDataType' => $recordField['isAnArray'] ? 'array' : $entityClassName
                ];
                $dataSetName[] = $recordField['DataSetName'];
                $this->registerEntity(
                    $recordField['DataSetName'],
                    $entityClassName,
                    $recordField['DataSetFields']
                );
            }
        }
        $entityClassName = $action;
        $this->registerEntity(
            $entityClassName,
            $entityClassName,
            $dataSetNames,
            true
        );
        $responseClassName = $entityClassName . 'Response';
        $this->generateCustomResponse(
            $entityDir,
            $baseNamespace,
            $output,
            $entityClassName,
            $responseClassName,
        );
        $this->generateCustomOperation(
            $operationDir,
            $baseNamespace,
            $output,
            $entityClassName,
            $requestClassName,
            $responseClassName,
            $operationNamespace,
            implode(',', $dataSetName)
        );
    }

    /**
     * Generate code for given non-replication webservice entity, operation and response code
     *
     * When dataset name is defined
     *
     * @param string $baseNamespace
     * @param string $operationNamespace
     * @param string $entityDir
     * @param string $operationDir
     * @param string $requestClassName
     * @param OutputInterface $output
     * @param array $recordFields
     * @param string $dataSetName
     * @return void
     */
    public function generateGivenNonReplicationWebserviceEntityOperationResponseCodeGivenDataSetName(
        string $baseNamespace,
        string $operationNamespace,
        string $entityDir,
        string $operationDir,
        string $requestClassName,
        OutputInterface $output,
        array $recordFields,
        string $dataSetName
    ) {
        $entityClassName = str_replace(' ', '', $dataSetName);
        $this->registerEntity(
            $dataSetName,
            $entityClassName,
            $recordFields
        );
        $responseClassName = $entityClassName . 'Response';
        $this->generateCustomResponse(
            $entityDir,
            $baseNamespace,
            $output,
            $entityClassName,
            $responseClassName,
        );
        $this->generateCustomOperation(
            $operationDir,
            $baseNamespace,
            $output,
            $entityClassName,
            $requestClassName,
            $responseClassName,
            $operationNamespace,
            implode(',', [$dataSetName])
        );
    }

    /**
     * Generate given replication webservice entity, operation and response classes code
     *
     * @param string $baseNamespace
     * @param string $operationNamespace
     * @param string $entityDir
     * @param string $operationDir
     * @param string $requestClassName
     * @param OutputInterface $output
     * @param array $recRef
     * @param array $recordFields
     * @return void
     */
    public function generateGivenReplicationWebserviceEntityOperationResponseCode(
        string $baseNamespace,
        string $operationNamespace,
        string $entityDir,
        string $operationDir,
        string $requestClassName,
        OutputInterface $output,
        array $recRef,
        array $recordFields
    ) {
        if (!empty($recRef) && !empty($recordFields)) {
            if (isset($recRef['TableName'])) {
                $name = $recRef['TableName'];
            } elseif (isset($recRef['DataSetName'])) {
                $name = $recRef['DataSetName'];
            }
            $entityClassName = str_replace(' ', '', $this->formatGivenValue($name));
            $this->registerEntity(
                $name,
                $entityClassName,
                $recordFields,
            );
            $responseClassName = $entityClassName . 'Response';
            $this->generateResponseContent(
                $entityDir,
                $baseNamespace,
                $output,
                $entityClassName,
                $responseClassName
            );

            $this->generateOperationContent(
                $operationDir,
                $baseNamespace,
                $output,
                $entityClassName,
                $requestClassName,
                $responseClassName,
                $operationNamespace
            );
        }
    }

    /**
     * Generate base request content
     *
     * @param string $entityDir
     * @param string $baseNamespace
     * @param OutputInterface $output
     * @return void
     */
    public function generateBaseRequestContent(string $entityDir, string $baseNamespace, OutputInterface $output)
    {
        $odataRequestBaseClassContent  = <<<PHP
<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */

namespace $baseNamespace;

class BaseODataRequest
{
    public int \$batchSize;
    public bool \$fullRepl;
    public string \$lastKey;
    public int \$lastEntryNo;

    public function __construct(array \$data = [])
    {
        \$this->batchSize = (int)(\$data['batchSize'] ?? 0);
        \$this->fullRepl = (bool)(\$data['fullRepl'] ?? false);
        \$this->lastKey = (string)(\$data['lastKey'] ?? '');
        \$this->lastEntryNo = (int)(\$data['lastEntryNo'] ?? 0);
    }

    public function getBatchSize(): int
    {
        return \$this->batchSize;
    }

    public function getFullRepl(): bool
    {
        return \$this->fullRepl;
    }

    public function getLastKey(): string
    {
        return \$this->lastKey;
    }

    public function getLastEntryNo(): int
    {
        return \$this->lastEntryNo;
    }
}
PHP;
        $odataRequestBaseClassFileName = AbstractGenerator::path($entityDir, "BaseODataRequest.php");
        $this->putGivenContentOnGivePath($odataRequestBaseClassFileName, $odataRequestBaseClassContent, $output);
    }

    /**
     * Generate base response content
     *
     * @param string $entityDir
     * @param string $baseNamespace
     * @param OutputInterface $output
     * @return void
     */
    public function generateBaseResponseContent(string $entityDir, string $baseNamespace, OutputInterface $output)
    {
        $odataResponseBaseClassContent  = <<<PHP
<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */

namespace $baseNamespace;

class BaseODataResponse
{
    public string \$status;
    public string \$errorText;
    public string \$lastKey;
    public int \$lastEntryNo;
    public bool \$endOfTable;

    public function __construct(array \$data = [])
    {
        \$this->status = (string)(\$data['status'] ?? '');
        \$this->errorText = (string)(\$data['errorText'] ?? '');
        \$this->lastKey = (string)(\$data['lastKey'] ?? '');
        \$this->lastEntryNo = (int)(\$data['lastEntryNo'] ?? 0);
        \$this->endOfTable = (bool)(\$data['endOfTable'] ?? false);
    }

    public function getStatus(): string
    {
        return \$this->status;
    }

    public function getErrorText(): string
    {
        return \$this->errorText;
    }

    public function getLastKey(): string
    {
        return \$this->lastKey;
    }

    public function getLastEntryNo(): int
    {
        return \$this->lastEntryNo;
    }

    public function getEndOfTable(): bool
    {
        return \$this->endOfTable;
    }
}
PHP;
        $odataResponseBaseClassFileName = AbstractGenerator::path($entityDir, "BaseODataResponse.php");
        $this->putGivenContentOnGivePath($odataResponseBaseClassFileName, $odataResponseBaseClassContent, $output);
    }

    /**
     * Generate request content
     *
     * @param string $entityDir
     * @param string $baseNamespace
     * @param OutputInterface $output
     * @param string $requestClassName
     * @param bool $hasStoreNo
     * @return void
     */
    public function generateRequestContent(
        string $entityDir,
        string $baseNamespace,
        OutputInterface $output,
        string $requestClassName,
        bool $hasStoreNo
    ) {
        $requestClassCode = <<<PHP
<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */

namespace $baseNamespace;

class $requestClassName extends BaseODataRequest
{
    public const ACTION_NAME = '$requestClassName';
PHP;
        $requestClassCode .= "\n";
        if ($hasStoreNo) {
            $requestClassCode .= <<<PHP
    public string \$storeNo;

    public function __construct(array \$data = [])
    {
        \$this->storeNo = (string)(\$data['storeNo'] ?? '');
        parent::__construct(\$data);
    }
PHP;
        }

        $requestClassCode .= <<<PHP

}
PHP;
        $requestClassPath = AbstractGenerator::path($entityDir, "{$requestClassName}.php");
        $this->putGivenContentOnGivePath($requestClassPath, $requestClassCode, $output);
    }

    /**
     * Generate entity content
     *
     * @param string $entityDir
     * @param string $baseNamespace
     * @param OutputInterface $output
     * @param string $entityClassName
     * @param array $recordFields
     * @param bool $recursive
     * @return void
     */
    public function generateEntityContent(
        string $entityDir,
        string $baseNamespace,
        OutputInterface $output,
        string $entityClassName,
        array $recordFields,
        bool $recursive = false,
    ) {
        $entityClassCode = <<<PHP
<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */

namespace $baseNamespace;

use Magento\Catalog\Model\AbstractModel;

class $entityClassName extends AbstractModel
{
PHP;
        $mapping = '';
        foreach ($recordFields as $field) {
            $fieldName = $field['FieldName'];
            if (strtolower($fieldName) == 'id') {
                $fieldName = 'Nav Id';
            }
            $optimizedFieldName = $this->formatGivenValue(ucwords(strtolower($fieldName)));
            $constName = str_replace(
                ' ',
                '_',
                strtoupper(preg_replace('/\B([A-Z])/', '_$1', $optimizedFieldName))
            );
            if ($recursive) {
//                $constIndexName = str_replace(
//                    ' ',
//                    '',
//                    $fieldName
//                );
                $constIndexName = $fieldName;
            } else {
                $constIndexName = $fieldName;
            }
            $columnName = strtolower($constName);
            $mapping .= "\n\tself::{$constName} => '{$columnName}',";

            $entityClassCode .= <<<PHP

    public const {$constName} = '{$constIndexName}';
PHP;
        }
        $entityClassCode .= "\n";
        $entityClassCode .= <<<PHP

    public static array \$dbColumnsMapping = [{$mapping}
    ];
PHP;

        $entityClassCode .= "\n";

        $entityClassCode .= <<<PHP

    public static function getDbColumnsMapping(): array
    {
        return self::\$dbColumnsMapping;
    }
PHP;
        $entityClassCode .= "\n";

        foreach ($recordFields as $field) {
            $fieldName = $field['FieldName'];
            $dataTypeRequired = true;
            if (strtolower($fieldName) == 'id') {
                $dataTypeRequired = false;
                $fieldName = 'Nav Id';
            }
            $fieldNameForMethodName = $this->formatGivenValue($fieldName, ' ');
            $fieldNameCapitalized = ucwords(strtolower($fieldNameForMethodName));
            $fieldNameCapitalized = str_replace(' ', '', $fieldNameCapitalized);
            $fieldName = $this->formatGivenValue(ucwords(strtolower($fieldName)));
            $constName = str_replace(' ', '_', strtoupper(preg_replace('/\B([A-Z])/', '_$1', $fieldName)));

            if ($recursive) {
                $phpType = $field['FieldDataType'];
            } else {
                $phpType = match (strtolower($field['FieldDataType'])) {
                    'integer', 'long', 'char', 'option' => 'int',
                    'decimal'    => 'float',
                    'boolean'    => 'bool',
                    default => 'string'
                };
            }

            $returnType = !$recursive && $dataTypeRequired ? ': ?'. $phpType : '';
            $phpType = $dataTypeRequired ? '?'.$phpType : '';
            $entityClassCode .= <<<PHP


    public function get$fieldNameCapitalized()$returnType
    {
        return \$this->getData(self::{$constName});
    }

    public function set$fieldNameCapitalized({$phpType} \$value)
    {
        return \$this->setData(self::{$constName}, \$value);
    }
PHP;
        }

        $entityClassCode .= <<<PHP

}
PHP;
        $entityClassPath = AbstractGenerator::path($entityDir, "{$entityClassName}.php");
        $this->putGivenContentOnGivePath($entityClassPath, $entityClassCode, $output);
    }

    /**
     * Generate response content
     *
     * @param string $entityDir
     * @param string $baseNamespace
     * @param OutputInterface $output
     * @param string $entityClassName
     * @param string $responseClassName
     * @return void
     */
    public function generateResponseContent(
        string $entityDir,
        string $baseNamespace,
        OutputInterface $output,
        string $entityClassName,
        string $responseClassName,
    ) {
        $responseClassCode = <<<PHP
<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */

namespace $baseNamespace;

class $responseClassName extends BaseODataResponse
{
    /** @var {$entityClassName}[] */
    public array \$records = [];

    public function __construct(array \$data)
    {
        \$this->records = \$data['records'];
        parent::__construct(\$data);
    }

    public function getRecords(): array
    {
        return \$this->records;
    }
}
PHP;
        $responseClassPath = AbstractGenerator::path($entityDir, "{$responseClassName}.php");
        $this->putGivenContentOnGivePath($responseClassPath, $responseClassCode, $output);
    }

    /**
     * Generate operation content
     *
     * @param string $operationDir
     * @param string $baseNamespace
     * @param OutputInterface $output
     * @param string $entityClassName
     * @param string $requestClassName
     * @param string $responseClassName
     * @param string $operationNamespace
     * @return void
     */
    public function generateOperationContent(
        string $operationDir,
        string $baseNamespace,
        OutputInterface $output,
        string $entityClassName,
        string $requestClassName,
        string $responseClassName,
        string $operationNamespace
    ) {
        $reqName = '\\' . $baseNamespace . '\\' . $requestClassName;
        $resName = '\\' . $baseNamespace . '\\' . $responseClassName;
        $entityName = '\\' . $baseNamespace . '\\' . $entityClassName;
        $operationClassCode = <<<PHP
<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */

namespace $operationNamespace;

use Magento\Framework\App\ObjectManager;

class $entityClassName
{
    public string \$baseUrl;
    public array \$connectionParams;
    public string \$companyName;
    public {$reqName} \$request;
    public {$resName} \$response;
    public \Ls\Omni\Helper\Data \$dataHelper;

    public function __construct(\$baseUrl = '', \$connectionParams = [], \$companyName = '')
    {
        \$this->baseUrl = \$baseUrl;
        \$this->connectionParams = \$connectionParams;
        \$this->companyName = \$companyName;
        \$this->dataHelper = \$this->createInstance(\\Ls\\Omni\\Helper\\Data::class);
        \$this->request = \$this->createInstance({$reqName}::class);
    }

    public function execute(): {$resName}
    {
        \$response = \$this->dataHelper->makeRequest(
            {$reqName}::ACTION_NAME,
            {$entityName}::class,
            \$this->request,
            \$this->baseUrl,
            \$this->connectionParams,
            ['company' => \$this->companyName]
        );

        \$response = \$this->formatResponse(\$response);

        \$this->setResponse(\$response);

        return \$response;
    }

    public function formatResponse(\$data): {$resName}
    {
        if (isset(\$data['TableData']['TableDataUpd']['RecRefJson'])) {
            \$recRef = \$data['TableData']['TableDataUpd']['RecRefJson'];
        } elseif (isset(\$data['DataSet']['DataSetUpd']['DynDataSet'])) {
            \$recRef = \$data['DataSet']['DataSetUpd']['DynDataSet'];
        } else {
            \$recRef = [];
        }

        \$deletedRows = [];
        if (isset(\$data['DataSet']['DataSetDel']['DynDataSet']['DataSetRows'])) {
            \$deletedRows = \$data['DataSet']['DataSetDel']['DynDataSet']['DataSetRows'];
        }

        if (isset(\$recRef['RecordFields'])) {
            \$fieldsDefinition = \$recRef['RecordFields'];
        } elseif (isset(\$recRef['DataSetFields'])) {
            \$fieldsDefinition = \$recRef['DataSetFields'];
        } else {
            \$fieldsDefinition = [];
        }

        \$deletedFieldsDefinition = [];
        if (isset(\$data['DataSet']['DataSetDel']['DynDataSet']['DataSetFields'])) {
            \$deletedFieldsDefinition = \$data['DataSet']['DataSetDel']['DynDataSet']['DataSetFields'];
        }

        if (isset(\$recRef['Records'])) {
            \$rows = \$recRef['Records'];
        } elseif (isset(\$recRef['DataSetRows'])) {
            \$rows = \$recRef['DataSetRows'];
        } else {
            \$rows = [];
        }


        foreach (\$fieldsDefinition as \$field) {
            \$fields[\$field['FieldIndex']] = \$field['FieldName'];
        }

        foreach (\$deletedFieldsDefinition as \$field) {
            \$deletedFields[\$field['FieldIndex']] = \$field['FieldName'];
        }

        \$results = [];
        if (!empty(\$fields)) {
            foreach (\$rows as \$row) {
                \$values = \$row['Fields'] ?? [];
                \$entry = \$this->createInstance(
                    {$entityName}::class
                );
                foreach (\$values as \$value) {
                    \$fieldName = \$fields[\$value['FieldIndex']];
                    if (strtolower(\$fieldName) == 'id') {
                        \$fieldName = 'Nav Id';
                    }
                    if (\$entry->getData(\$fieldName) === null) {
                        \$entry->setData(\$fieldName, \$value['FieldValue']);
                    }
                }
                \$results[] = \$entry;
            }
        }

        if (!empty(\$deletedFields)) {
            foreach (\$deletedRows as \$row) {
                \$values = \$row['Fields'] ?? [];
                \$entry = \$this->createInstance(
                    {$entityName}::class
                );
                \$entry->setData('is_deleted', true);
                foreach (\$values as \$value) {
                    \$fieldName = \$deletedFields[\$value['FieldIndex']];
                    if (strtolower(\$fieldName) == 'id') {
                        \$fieldName = 'Nav Id';
                    }
                    if (\$entry->getData(\$fieldName) === null) {
                        \$entry->setData(\$fieldName, \$value['FieldValue']);
                    }
                }
                \$results[] = \$entry;
            }
        }

        return \$this->createInstance(
                    {$resName}::class,
                     [
                        'data' => [
                        'records' => \$results,
                        'status' => \$data['Status'] ?? '',
                        'errorText' => \$data['ErrorText'] ?? '',
                        'lastKey' => \$data['LastKey'] ?? '',
                        'lastEntryNo' => \$data['LastEntryNo'] ?? 0,
                        'endOfTable' => \$data['EndOfTable'] ?? false
                        ]
                     ]
                );
    }

    public function createInstance(string \$entityClassName, array \$data = [])
    {
        return ObjectManager::getInstance()->create(\$entityClassName, \$data);
    }

    public function & setOperationInput(array \$params = []): {$reqName}
    {
        \$this->setRequest(
            \$this->createInstance(
                {$reqName}::class,
                ['data' => \$params]
            )
        );
        \$request = \$this->getRequest();

        return \$request;
    }


    public function setRequest({$reqName} \$request): self
    {
        \$this->request = \$request;
        return \$this;
    }

    public function setResponse({$resName} \$response): self
    {
        \$this->response = \$response;
        return \$this;
    }

    public function getRequest(): {$reqName}
    {
        return \$this->request;
    }

    public function getResponse(): {$resName}
    {
        return \$this->response;
    }
}
PHP;
        $operationClassPath = AbstractGenerator::path($operationDir, "{$entityClassName}.php");
        $this->putGivenContentOnGivePath($operationClassPath, $operationClassCode, $output);
    }

    /**
     * Generate a request class for non-ODataRequest_ actions
     *
     * @param string $entityDir
     * @param string $baseNamespace
     * @param OutputInterface $output
     * @param string $requestClassName
     * @param string $action
     * @param mixed $params
     * @return void
     */
    public function generateCustomRequest(
        string $entityDir,
        string $baseNamespace,
        OutputInterface $output,
        string $requestClassName,
        string $action,
        $params
    ) {
        $props                  = '';
        $constructorAssignments = '';

        foreach ($params as $param) {
            $name = $param->getAttribute('Name');
            $type = strtolower($param->getAttribute('Type'));

            $phpType = match ($type) {
                'edm.boolean' => 'bool',
                'edm.int32', 'edm.int64' => 'int',
                'edm.decimal' => 'float',
                'edm.datetime', 'edm.datetimeoffset' => '\DateTime|string',
                default => 'string'
            };

            $props .= <<<PHP

    public $phpType \$$name;
PHP;
            $defaultValue = '\'\'';

            if ($phpType == 'bool') {
                $defaultValue = 'true';
            } elseif ($phpType == 'int') {
                $defaultValue = 0;
            } elseif ($phpType == 'float') {
                $defaultValue = 0.0;
            }

            $constructorAssignments .= <<<PHP
        \$this->$name = \$data['$name'] ?? $defaultValue;

PHP;
        }

        $requestClassCode = <<<PHP
<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */

namespace $baseNamespace;

class $requestClassName
{
    public const ACTION_NAME = '$action';
PHP;
        if (!empty($props)) {
            $requestClassCode .= <<<PHP
$props

    public function __construct(array \$data = [])
    {
$constructorAssignments    }
}
PHP;
        } else {
            $requestClassCode .= <<<PHP

}
PHP;
        }

        $requestClassPath = AbstractGenerator::path($entityDir, "{$requestClassName}.php");
        $this->putGivenContentOnGivePath($requestClassPath, $requestClassCode, $output);
    }

    /**
     * Generate a response class for non-ODataRequest_ actions
     *
     * @param string $entityDir
     * @param string $baseNamespace
     * @param OutputInterface $output
     * @param string $entityClassName
     * @param string $responseClassName
     * @param array $topLevelKeys
     * @return void
     */
    public function generateCustomResponse(
        string $entityDir,
        string $baseNamespace,
        OutputInterface $output,
        string $entityClassName,
        string $responseClassName,
        array $topLevelKeys = ['ResponseCode', 'ErrorText']
    ) {
        $responseProps = '';
        $constructorAssignments = '';

        foreach ($topLevelKeys as $key) {
            $prop = lcfirst($key);
            $responseProps .= "    public string \$$prop;\n";
            $constructorAssignments .= "        \$this->$prop = (string)(\$data['$key'] ?? '');\n";
        }

        $responseClassCode = <<<PHP
<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */

namespace $baseNamespace;

class $responseClassName
{
$responseProps
    /** @var {$entityClassName}[] */
    public array \$records = [];

    public function __construct(array \$data = [])
    {
$constructorAssignments
         \$this->records = \$data['records'] ?? [];
    }

    public function getRecords(): array
    {
        return \$this->records;
    }

    public function getResponseCode(): string
    {
        return \$this->responseCode;
    }

    public function getErrorText(): string
    {
        return \$this->errorText;
    }
}
PHP;

        $responseClassPath = AbstractGenerator::path($entityDir, "{$responseClassName}.php");
        $this->putGivenContentOnGivePath($responseClassPath, $responseClassCode, $output);
    }

    /**
     * Generate an operation class for non-ODataRequest_ actions
     *
     * @param string $operationDir
     * @param string $baseNamespace
     * @param OutputInterface $output
     * @param string $entityClassName
     * @param string $requestClassName
     * @param string $responseClassName
     * @param string $operationNamespace
     * @param mixed $targetDataSetName
     * @return void
     */
    public function generateCustomOperation(
        string $operationDir,
        string $baseNamespace,
        OutputInterface $output,
        string $entityClassName,
        string $requestClassName,
        string $responseClassName,
        string $operationNamespace,
        $targetDataSetName
    ) {
        $reqFqcn = '\\' . $baseNamespace . '\\' . $requestClassName;
        $resFqcn = '\\' . $baseNamespace . '\\' . $responseClassName;
        $entityFqcn = '\\' . $baseNamespace . '\\' . $entityClassName;
        $operationClassCode = <<<PHP
<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */

namespace $operationNamespace;

use Magento\Framework\App\ObjectManager;

class $entityClassName
{
    public string \$baseUrl;
    public array \$connectionParams;
    public string \$companyName;
    public $reqFqcn \$request;
    public $resFqcn \$response;
    public \Ls\Omni\Helper\Data \$dataHelper;

    public function __construct(string \$baseUrl = '', array \$connectionParams = [], string \$companyName = '')
    {
        \$this->baseUrl = \$baseUrl;
        \$this->connectionParams = \$connectionParams;
        \$this->companyName = \$companyName;
        \$this->dataHelper = \$this->createInstance(\\Ls\\Omni\\Helper\\Data::class);
        \$this->request = \$this->createInstance({$reqFqcn}::class);
    }

    public function execute(): $resFqcn
    {
        \$raw = \$this->dataHelper->makeRequest(
            $reqFqcn::ACTION_NAME,
            self::class,
            \$this->request,
            \$this->baseUrl,
            \$this->connectionParams,
            ['company' => \$this->companyName]
        );

        \$response = \$this->formatResponse(\$raw);
        \$this->setResponse(\$response);

        return \$response;
    }

    public function formatResponse(\$data): $resFqcn
    {
        \$requiredDataSetName = explode(',', '$targetDataSetName');
        \$finalEntry = \$this->createInstance({$entityFqcn}::class);
        if (is_array(\$requiredDataSetName)) {
            foreach (\$requiredDataSetName as \$dataSet) {
                \$entityClassName = str_replace(' ', '', preg_replace('/[\/\[\]()$\-._%&]/', '', \$dataSet));
                // Try flat response structure
                if (isset(\$data[\$dataSet]) && is_array(\$data[\$dataSet])) {
                    \$entity = \$this->createInstance(
                        {$entityFqcn}::class,
                         ['data' => \$data['$targetDataSetName']]
                     );

                    return \$this->createInstance(
                        {$resFqcn}::class,
                       [
                       'data' =>
                           [
                                'records' => [\$entity],
                                'ResponseCode' => \$data['ResponseCode'] ?? '',
                                'ErrorText' => \$data['ErrorText'] ?? '',
                           ]
                       ]
                    );
                }
                \$fields = \$rows = [];
                \$recRef = \$this->findNestedDataSet(\$data, \$entityClassName);
                if (\$recRef && isset(\$recRef['DataSetFields'], \$recRef['DataSetRows'])) {
                    if (isset(\$recRef['DataSetFields'])) {
                        foreach (\$recRef['DataSetFields'] as \$field) {
                            \$fields[\$field['FieldIndex']] = \$field['FieldName'];
                        }
                    }

                    if (isset(\$recRef['DataSetRows'])) {
                        \$rows = \$recRef['DataSetRows'];
                    }
                    \$className = '\\$baseNamespace'.'\\\'.\$entityClassName;
                    \$count = count(\$rows);
                    \$entries = [];
                    foreach (\$rows as \$index => \$row) {
                        \$entry = \$this->createInstance(\$className);
                        foreach (\$row['Fields'] ?? [] as \$field) {
                            \$entry->setData(\$fields[\$field['FieldIndex']], \$field['FieldValue']);
                        }
                        \$entries[\$index] = \$entry;
                    }
                    if (!empty(\$entries)) {
                        \$finalEntry->setData(\$dataSet, \$count > 1 ? \$entries : current(\$entries));
                    }
                }
            }
            return \$this->createInstance(
                {$resFqcn}::class,
                [
                'data' =>
                    [
                        'records' => [\$finalEntry],
                        'ResponseCode' => \$data['ResponseCode'] ?? '',
                        'ErrorText' => \$data['ErrorText'] ?? '',
                    ]
                ]
            );
        }
        return \$this->createInstance(
            {$resFqcn}::class,
             [
             'data' =>
                 [
                    'records' => [],
                    'ResponseCode' => \$data['ResponseCode'] ?? '',
                    'ErrorText' => \$data['ErrorText'] ?? 'Unable to parse response.',
                 ]
            ]
        );
    }

    public function findNestedDataSet(\$data, string \$target): ?array
    {
        if (is_array(\$data)) {
            foreach (\$data as \$key => \$value) {
                if (is_array(\$value) || is_object(\$value)) {
                    \$found = \$this->findNestedDataSet((array)\$value, \$target);
                    if (!empty(\$found)) {
                        return \$found;
                    }
                }

                if (
                    is_array(\$data)
                    && isset(\$data['DataSetName'])
                    && str_replace(' ', '', preg_replace('/[\/\[\]()$\-._%&]/', '', \$data['DataSetName'])) === \$target
                ) {
                    return \$data;
                }
            }
        }

        return null;
    }

    public function createInstance(string \$entityClassName, array \$data = [])
    {
        return ObjectManager::getInstance()->create(\$entityClassName, \$data);
    }

    public function & setOperationInput(array \$params = []): $reqFqcn
    {
        \$this->setRequest(
            \$this->createInstance(
                {$reqFqcn}::class,
                ['data' => \$params]
            )
        );
        \$request = \$this->getRequest();

        return \$request;
    }

    public function setRequest($reqFqcn \$request): self
    {
        \$this->request = \$request;
        return \$this;
    }

    public function setResponse($resFqcn \$response): self
    {
        \$this->response = \$response;
        return \$this;
    }

    public function getRequest(): $reqFqcn
    {
        return \$this->request;
    }

    public function getResponse(): $resFqcn
    {
        return \$this->response;
    }
}
PHP;

        $operationClassPath = AbstractGenerator::path($operationDir, "{$entityClassName}.php");
        $this->putGivenContentOnGivePath($operationClassPath, $operationClassCode, $output);
    }

    /**
     * Fetch fields and records
     *
     * @param Data $omniDataHelper
     * @param string $requestClassName
     * @param bool $hasStoreNo
     * @param string $storeCode
     * @return array
     * @throws GuzzleException
     * @throws NoSuchEntityException
     */
    public function fetchGivenReplicationWebserviceFieldsAndRecords(
        Data $omniDataHelper,
        string $requestClassName,
        bool $hasStoreNo,
        string $storeCode
    ) {
        $payload = [
            'batchSize' => 1,
            'fullRepl' => true,
            'lastKey' => '',
            'lastEntryNo' => 0
        ];

        if ($hasStoreNo) {
            $payload['storeNo'] = $storeCode;
        }
        $data = $omniDataHelper->fetchGivenOdata(
            $requestClassName,
            '',
            [],
            [],
            $payload
        );

        // Extract fields from the first TableDataUpd section
        if (isset($data['TableData']['TableDataUpd']['RecRefJson'])) {
            $recRef = $data['TableData']['TableDataUpd']['RecRefJson'];
        } elseif (isset($data['DataSet']['DataSetUpd']['DynDataSet'])) {
            $recRef = $data['DataSet']['DataSetUpd']['DynDataSet'];
        } else {
            $recRef = [];
        }

        if (isset($recRef['RecordFields'])) {
            $recordFields = $recRef['RecordFields'];
        } elseif (isset($recRef['DataSetFields'])) {
            $recordFields = $recRef['DataSetFields'];
        } else {
            $recordFields = [];
        }

        return [$recRef, $recordFields];
    }

    /**
     * Segregate webservices
     *
     * @param Data $omniDataHelper
     * @return void
     * @throws GuzzleException
     * @throws NoSuchEntityException
     */
    public function segregateWebservices(Data $omniDataHelper)
    {
        $odataMetaData = $omniDataHelper->fetchOdataV4Xml();
        $actions = $odataMetaData->query('//edm:Action');

        foreach ($actions as $action) {
            $requestClassName = $action->getAttribute('Name');
            $params           = $odataMetaData->query('edm:Parameter', $action);

            $hasFullRepl = $hasStoreNo = false;
            foreach ($params as $param) {
                $paramName = $param->getAttribute('Name');

                if ($paramName === 'fullRepl') {
                    $hasFullRepl = true;
                }

                if ($paramName === 'storeNo') {
                    $hasStoreNo = true;
                }
            }

            $request = [
                'hasStoreNo' => $hasStoreNo,
                'requestClassName' => $requestClassName,
                'params' => $params,
            ];

            if ($hasFullRepl) {
                $this->replication[] = $request;
            } else {
                $this->nonReplication[] = $request;
            }
        }
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
        // Step 1: Remove special characters
        $cleaned = preg_replace('/[\/\[\]()$\-._%&]/', $replaceWith, $value);
        // Step 2: Replace multiple spaces with a single space
        $cleaned = preg_replace('/ {2,}/', ' ', $cleaned);

        return trim($cleaned);
    }

    /**
     * Put given content on given path
     *
     * @param string $path
     * @param string $content
     * @param OutputInterface $output
     * @return void
     */
    public function putGivenContentOnGivePath(string $path, string $content, OutputInterface $output)
    {
        // @codingStandardsIgnoreLine
        file_put_contents($path, $content);
        $fs  = new Filesystem();
        $cwd = getcwd();
        $ok  = sprintf('generated entity ( %1$s )', $fs->makePathRelative($path, $cwd));
        $output->writeln($ok);
    }

    /**
     * Recursively search for a DataSetName or matching key and return field list.
     *
     * @param mixed $data
     * @param string $targetName
     * @return array|null
     */
    public function findDataSetFieldsRecursive($data, string $targetName): ?array
    {
        $result = [];

        // Check if the data is an array
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                // If specific targetName is provided and matches a key, return its value
                if ($targetName !== '' && $key === $targetName && is_array($value)) {
                    return array_map(
                        fn($fieldName) => [
                            'FieldName' => $fieldName,
                            'FieldDataType' => gettype($value[$fieldName])
                        ],
                        array_keys($value)
                    );
                }
                // If targetName is empty, collect all DataSetName and their fields
                if ($targetName === '') {
                    // Check if the current data is a dataset with 'DataSetName' and 'DataSetFields'
                    if (isset($value['DataSetName']) && isset($value['DataSetFields'])) {
                        // Collect only FieldName and FieldDataType for each field
                        $fields = array_map(function ($field) {
                            return [
                                'FieldName' => $field['FieldName'],
                                'FieldDataType' => $field['FieldDataType']
                            ];
                        }, $value['DataSetFields']);

                        // Add the dataset to the result array
                        $result[] = [
                            'DataSetName' => $value['DataSetName'],
                            'DataSetFields' => $fields,
                            'isAnArray' => count($value['DataSetRows']) > 1
                        ];
                    }
                } elseif (isset($value['DataSetName']) &&
                    $value['DataSetName'] === $targetName &&
                    isset($value['DataSetFields'])
                ) {
                    // Collect only FieldName and FieldDataType for each field
                    return array_map(function ($field) {
                        return [
                            'FieldName' => $field['FieldName'],
                            'FieldDataType' => $field['FieldDataType']
                        ];
                    }, $value['DataSetFields']);
                }

                // Recurse into nested structures to find DataSetName or matching key
                if (is_array($value) || is_object($value)) {
                    $found = $this->findDataSetFieldsRecursive((array) $value, $targetName);
                    if (!empty($found)) {
                        // Merge the result of recursive call with the current result
                        $result = array_merge($result, $found);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Register entities collection
     *
     * @param string $unsanitizedEntityClassName
     * @param string $sanitizedEntityClassName
     * @param array $recordFields
     * @param bool $recursive
     * @return void
     */
    public function registerEntity(
        string $unsanitizedEntityClassName,
        string $sanitizedEntityClassName,
        array $recordFields,
        bool $recursive = false
    ) {
        // Normalize fields by FieldName
        $mergedFields = [];
        foreach ($recordFields as $field) {
            if (!isset($field['FieldName']) || !isset($field['FieldDataType'])) {
                continue; // Skip if required keys are missing
            }
            $mergedFields[$field['FieldName']] = [
                'FieldName' => $field['FieldName'],
                'FieldDataType' => $field['FieldDataType']
            ];
        }

        if (!isset($this->entities[$unsanitizedEntityClassName])) {
            $this->entities[$unsanitizedEntityClassName] = [
                'sanitizedClassName' => $sanitizedEntityClassName,
                'RecordFields' => array_values($mergedFields),
                'recursive' => $recursive
            ];
        } else {
            // Merge with existing fields
            $existingFields = $this->entities[$unsanitizedEntityClassName]['RecordFields'] ?? [];
            foreach ($existingFields as $field) {
                if (!isset($mergedFields[$field['FieldName']])) {
                    $mergedFields[$field['FieldName']] = $field;
                }
            }
            // Custom sort: alphabetic names first, non-alphabetic ones after
            uksort($mergedFields, function ($a, $b) {
                $aAlpha = ctype_alpha($a[0]);
                $bAlpha = ctype_alpha($b[0]);

                if ($aAlpha && !$bAlpha) {
                    return -1;
                } elseif (!$aAlpha && $bAlpha) {
                    return 1;
                } else {
                    return strcasecmp($a, $b);
                }
            });

            $this->entities[$unsanitizedEntityClassName] = [
                'sanitizedClassName' => $sanitizedEntityClassName,
                'RecordFields' => array_values($mergedFields),
                'recursive' => $recursive
            ];
        }
    }

    /**
     * Get replication services
     *
     * @return array
     */
    public function getReplicationServices()
    {
        return $this->replication;
    }

    /**
     * Get non-replication webservices
     *
     * @return array
     */
    public function getNonReplicationServices()
    {
        return $this->nonReplication;
    }
}
