<?php

namespace Ls\Omni\Code;

use GuzzleHttp\Exception\GuzzleException;
use Ls\Core\Code\AbstractGenerator;
use Ls\Omni\Helper\Data;
use Magento\Framework\Exception\NoSuchEntityException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class OdataGenerator
{
    /**
     * @var array[]
     */
    public $allowedNonReplActions = [
        'GetStores_GetStores' => [
          'request' => [
              'storeGetType' => '1',
              'searchText' => '',
              'includeDetail' => false
          ],
          'response' => [
              'DataSetName' => 'LSC Store'
          ]
        ],
        'TestConnectionOData_TestConnection' => [
            'request' => [],
            'response' => [
                'indexName' => 'TestConnectionResponse'
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
     * @return void
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
        $odataMetaData      = $omniDataHelper->fetchOdataV4Xml();
        $this->generateBaseRequestContent($entityDir, $baseNamespace, $output);
        $this->generateBaseResponseContent($entityDir, $baseNamespace, $output);
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

            if ($hasFullRepl) {
                $this->generateRequestContent($entityDir, $baseNamespace, $output, $requestClassName, $hasStoreNo);

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

                if (!empty($recRef) && !empty($recordFields)) {
                    if (isset($recRef['TableName'])) {
                        $name = $recRef['TableName'];
                    } elseif (isset($recRef['DataSetName'])) {
                        $name = $recRef['DataSetName'];
                    }
                    $entityClassName = str_replace(' ', '', $name);
                    $this->generateEntityContent(
                        $entityDir,
                        $baseNamespace,
                        $output,
                        $entityClassName,
                        $recordFields
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
            } else {
                if (isset($this->allowedNonReplActions[$requestClassName])) {
                    $this->generateCustomRequest($entityDir, $baseNamespace, $output, $requestClassName, $params);

                    $data = $omniDataHelper->fetchGivenOdata(
                        $requestClassName,
                        '',
                        [],
                        [],
                        $this->allowedNonReplActions[$requestClassName]['request'],
                    );
                    $dataSetName = $this->allowedNonReplActions[$requestClassName]['response']['DataSetName'] ??
                        $this->allowedNonReplActions[$requestClassName]['response']['indexName'];
                    $recordFields = $this->findDataSetFieldsRecursive($data, $dataSetName);

                    if (!empty($recordFields)) {
                        $entityClassName = str_replace(' ', '', $dataSetName);
                        $this->generateEntityContent(
                            $entityDir,
                            $baseNamespace,
                            $output,
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
                            $dataSetName
                        );
                    }
                }
            }
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

    public function __construct(array \$data)
    {
        \$this->batchSize = (int)(\$data['batchSize'] ?? 0);
        \$this->fullRepl = (bool)(\$data['fullRepl'] ?? false);
        \$this->lastKey = (string)(\$data['lastKey'] ?? '');
        \$this->lastEntryNo = (int)(\$data['lastEntryNo'] ?? 0);
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

    public function __construct(array \$data)
    {
        \$this->status = (string)(\$data['status'] ?? '');
        \$this->errorText = (string)(\$data['errorText'] ?? '');
        \$this->lastKey = (string)(\$data['lastKey'] ?? '');
        \$this->lastEntryNo = (int)(\$data['lastEntryNo'] ?? 0);
        \$this->endOfTable = (bool)(\$data['endOfTable'] ?? false);
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

    public function __construct(array \$data)
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
     * @return void
     */
    public function generateEntityContent(
        string $entityDir,
        string $baseNamespace,
        OutputInterface $output,
        string $entityClassName,
        array $recordFields
    ) {
        $entityClassCode = <<<PHP
<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */

namespace $baseNamespace;

use Magento\Framework\DataObject;

class $entityClassName extends DataObject
{
PHP;

        foreach ($recordFields as $field) {
            $optimizedFieldName = $this->formatGivenValue($field['FieldName']);
            $constName = str_replace(
                ' ',
                '_',
                strtoupper(preg_replace('/\B([A-Z])/', '_$1', $optimizedFieldName))
            );
            $entityClassCode .= <<<PHP

    public const {$constName} = '{$field['FieldName']}';
PHP;
        }

        $entityClassCode .= "\n";

        foreach ($recordFields as $field) {
            $fieldNameForMethodName = $this->formatGivenValue($field['FieldName'], ' ');
            $fieldNameCapitalized = ucwords($fieldNameForMethodName);
            $fieldNameCapitalized = str_replace(' ', '', $fieldNameCapitalized);
            $fieldName = $this->formatGivenValue($field['FieldName']);
            $constName = str_replace(' ', '_', strtoupper(preg_replace('/\B([A-Z])/', '_$1', $fieldName)));
            $phpType = match (strtolower($field['FieldDataType'])) {
                'integer' => 'int',
                'datetime' => '\DateTime',
                'boolean' => 'bool',
                default => 'string'
            };

            $entityClassCode .= <<<PHP

    public function get$fieldNameCapitalized(): ?$phpType
    {
        return \$this->getData(self::{$constName});
    }

    public function set$fieldNameCapitalized($phpType \$value): self
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
        \$this->dataHelper = ObjectManager::getInstance()->get(\Ls\Omni\Helper\Data::class);
    }

    public function execute({$reqName} \$request = null): {$resName}
    {
        if ( !is_null( \$request ) ) {
            \$this->setRequest( \$request );
        }

        \$response = \$this->dataHelper->makeRequest(
            {$reqName}::ACTION_NAME,
            {$entityName}::class,
            \$request,
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

        if (isset(\$recRef['RecordFields'])) {
            \$fieldsDefinition = \$recRef['RecordFields'];
        } elseif (isset(\$recRef['DataSetFields'])) {
            \$fieldsDefinition = \$recRef['DataSetFields'];
        } else {
            \$fieldsDefinition = [];
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
        \$results = [];
        if (!empty(\$fields)) {
            foreach (\$rows as \$row) {
                \$values = \$row['Fields'] ?? [];
                \$entry = new {$entityName}();
                foreach (\$values as \$value) {
                    \$entry->setData(\$fields[\$value['FieldIndex']], \$value['FieldValue']);
                }
                \$results[] = \$entry;
            }
        }

        return new {$resName}([
            'records' => \$results,
            'status' => \$data['Status'] ?? '',
            'errorText' => \$data['ErrorText'] ?? '',
            'lastKey' => \$data['LastKey'] ?? '',
            'lastEntryNo' => \$data['LastEntryNo'] ?? 0,
            'endOfTable' => \$data['EndOfTable'] ?? false
        ]);
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
     * @param mixed $params
     * @return void
     */
    public function generateCustomRequest(
        string          $entityDir,
        string          $baseNamespace,
        OutputInterface $output,
        string          $requestClassName,
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

            $constructorAssignments .= <<<PHP
        \$this->$name = \$data['$name'] ?? null;

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
    public const ACTION_NAME = '$requestClassName';
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
     * @param string $targetDataSetName
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
        string $targetDataSetName
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
        \$this->dataHelper = ObjectManager::getInstance()->get(\\Ls\\Omni\\Helper\\Data::class);
    }

    public function execute($reqFqcn \$request = null): $resFqcn
    {
        if (\$request !== null) {
            \$this->setRequest(\$request);
        }

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
        \$fields = [];
        \$rows = [];

        \$recRef = \$this->findNestedDataSet(\$data, '$targetDataSetName');
        if (\$recRef && isset(\$recRef['DataSetFields'], \$recRef['DataSetRows'])) {
            if (isset(\$recRef['DataSetFields'])) {
                foreach (\$recRef['DataSetFields'] as \$field) {
                    \$fields[\$field['FieldIndex']] = \$field['FieldName'];
                }
            }

            if (isset(\$recRef['DataSetRows'])) {
                \$rows = \$recRef['DataSetRows'];
            }

            \$entities = [];
            foreach (\$rows as \$row) {
                \$entry = new $entityFqcn();
                foreach (\$row['Fields'] ?? [] as \$field) {
                    \$entry->setData(\$fields[\$field['FieldIndex']], \$field['FieldValue']);
                }
                \$entities[] = \$entry;
            }

            return new $resFqcn([
                'records' => \$entities,
                'ResponseCode' => \$data['ResponseCode'] ?? '',
                'ErrorText' => \$data['ErrorText'] ?? ''
            ]);
        }


            // Try flat response structure
        if (isset(\$data['$targetDataSetName']) && is_array(\$data['$targetDataSetName'])) {
            \$entity = new $entityFqcn(\$data['$targetDataSetName']);

            return new $resFqcn([
                'records' => [\$entity],
                'ResponseCode' => \$data['ResponseCode'] ?? '',
                'ErrorText' => \$data['ErrorText'] ?? '',
            ]);
        }

        // Fallback
        return new $resFqcn([
            'records' => [],
            'ResponseCode' => \$data['ResponseCode'] ?? '',
            'ErrorText' => \$data['ErrorText'] ?? 'Unable to parse response.',
        ]);
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
                    && \$data['DataSetName'] === \$target
                ) {
                    return \$data;
                }
            }
        }

        return null;
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
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if ($key === $targetName && is_array($value)) {
                    return array_map(
                        fn($fieldName) => [
                            'FieldName' => $fieldName,
                            'FieldDataType' => gettype($value[$fieldName])
                        ],
                        array_keys($value)
                    );
                }

                if (isset($data['DataSetName']) &&
                    $data['DataSetName'] === $targetName &&
                    isset($data['DataSetFields'])
                ) {
                    return $data['DataSetFields'];
                }

                // Recurse into nested structures
                if (is_array($value) || is_object($value)) {
                    $found = $this->findDataSetFieldsRecursive((array) $value, $targetName);
                    if (!empty($found)) {
                        return $found;
                    }
                }
            }
        }

        return null;
    }
}
