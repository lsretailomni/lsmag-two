<?php

namespace Ls\Omni\Client;

use DOMDocument;
use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Exception\NavException;
use \Ls\Omni\Exception\NavObjectReferenceNotAnInstanceException;
use \Ls\Omni\Helper\CacheHelper;
use \Ls\Omni\Helper\Data;
use \Ls\Omni\Service\ServiceType;
use \Ls\Replication\Logger\FlatReplicationLogger;
use \Ls\Replication\Logger\OmniLogger;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Session\SessionManagerInterface;
use Psr\Log\LoggerInterface;
use SoapFault;

/**
 * Class AbstractOperation
 * @package Ls\Omni\Client
 */
abstract class AbstractOperation implements OperationInterface
{
    /**
     * @var string
     */
    private static $header = 'LSRETAIL-KEY';

    /**
     * @var ServiceType
     */
    public $service_type;

    /**
     * @var null
     */
    public $token = null;

    /**
     * @var OmniLogger
     */
    public $omniLogger;

    /**
     * @var Stream
     */
    public $writer;

    /**
     * @var mixed
     */
    public $magentoLogger;

    /**
     * @var ObjectManager
     */
    public $objectManager;

    /**
     * @var SessionManagerInterface
     */
    public $session;

    /**
     * @var CacheHelper
     */
    public $cacheHelper;

    /**
     * @var FlatReplicationLogger
     */
    public $flatReplicationLogger;

    /**
     * @var Data
     */
    public $dataHelper;

    /**
     * Constructor function
     */
    public function __construct()
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->omniLogger = $this->objectManager->get(OmniLogger::class);
        $this->magentoLogger = $this->objectManager->get(LoggerInterface::class);
        $this->flatReplicationLogger = $this->objectManager->get(FlatReplicationLogger::class);
        $this->session = $this->objectManager->get(SessionManagerInterface::class);
        $this->cacheHelper = $this->objectManager->get(CacheHelper::class);
        $this->dataHelper  = $this->objectManager->get(Data::class);
    }

    /**
     * @return ServiceType
     */
    public function getServiceType()
    {
        return $this->service_type;
    }

    /**
     * @return bool
     */
    /** If we change this then we need to change our generator classes in Client/Loyalty Folder as well. */
    // @codingStandardsIgnoreStart
    /**
     * @param $operation_name
     * @return NavException|NavObjectReferenceNotAnInstanceException|string|null
     * @throws Exception
     */
    public function makeRequest($operation_name)
    {
        $request_input = $this->getOperationInput();
        $client        = $this->getClient();
        $response      = null;
        $lsr           = $this->objectManager->get("\Ls\Core\Model\LSR");
        $client->setLocation($client->getWSDL());
        //@codingStandardsIgnoreEnd
        $requestTime = \DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));
        try {
            $isDataObject = $request_input instanceof \Magento\Framework\DataObject;
            $response = $client->{$operation_name}(
                $isDataObject ?
                    $request_input->getData() :
                    $request_input
            );
            if (is_object($response)) {
                if ($isDataObject) {
                    $response = $this->convertSoapResponseToDataObject($response);
                }
            }

            if ($operation_name == 'OrderCreate') {
                $lsr->setLicenseValidity("1");
            }
        } catch (SoapFault $e) {
            $navException = $this->parseException($e);
            $this->magentoLogger->critical($navException);
            if ($e->getMessage() != "") {
                if ($e->faultcode == 's:Error' && $operation_name == 'OneListCalculate') {
                    $response = $e->getMessage();
                } elseif ($e->getCode() == 504 && $operation_name == 'ContactCreate') {
                    $response = null;
                } elseif ($operation_name == 'Ping') {
                    throw new Exception('Unable to ping commerce service.');
                } elseif ($operation_name == 'OrderCreate' &&
                    $e->faultcode == 's:GeneralErrorCode' &&
                    str_contains($e->faultstring, 'LS Central Ecom unit')
                ) {
                    $lsr->setLicenseValidity("0");
                }
            } else {
                $response = null;
            }
            $cacheId = LSR::PING_RESPONSE_CACHE . $lsr->getCurrentWebsiteId();
            $this->cacheHelper->removeCachedContent($cacheId);
        }
        $responseTime = \DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));

        $this->debugLog($operation_name, $requestTime, $responseTime, $lsr->getWebsiteId());

        return $response;
    }
    // @codingStandardsIgnoreEnd

    private function convertSoapResponseToDataObject($response): \Magento\Framework\DataObject
    {
        $data = [];

        foreach (get_object_vars($response) as $key => $value) {
            // Handle nested objects recursively
            if (is_object($value)) {
                $data[$key] = $this->convertSoapResponseToDataObject($value);
            } elseif (is_array($value)) {
                $data[$key] = $this->convertSoapResponseArray($value);
            } else {
                $data[$key] = $value;
            }
        }

        return new \Magento\Framework\DataObject($data);
    }

    private function convertSoapResponseArray(array $array): array
    {
        $result = [];
        foreach ($array as $key => $item) {
            if (is_object($item)) {
                $result[$key] = $this->convertSoapResponseToDataObject($item);
            } elseif (is_array($item)) {
                $result[$key] = $this->convertSoapResponseArray($item);
            } else {
                $result[$key] = $item;
            }
        }
        return $result;
    }

    /**
     * @param string $token
     *
     * @return $this
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @param SoapFault $exception
     * @return NavException|NavObjectReferenceNotAnInstanceException
     */
    public function parseException(SoapFault $exception)
    {
        //@codingStandardsIgnoreStart
        $navException = new NavException($exception->getMessage(), 1, $exception);
        if (strpos($exception->getMessage(), "Object reference not set to an instance of an object") !== -1) {
            $navException = new NavObjectReferenceNotAnInstanceException($exception->getMessage(), 1, $exception);
        }

        //@codingStandardsIgnoreEnd
        return $navException;
    }

    /**
     * Log request, response and time elapsed
     *
     * @param $operationName
     * @param $requestTime
     * @param $responseTime
     * @param $websiteId
     * @return void
     */
    private function debugLog($operationName, $requestTime, $responseTime, $websiteId)
    {
        //@codingStandardsIgnoreStart
        $lsr = $this->objectManager->get("\Ls\Core\Model\LSR");
        //@codingStandardsIgnoreEnd
        try {
            $sessionValue = $this->getValue();
            $disableLog   = $operationName == 'Ping' && $sessionValue == null;
        } catch (Exception $e) {
            $disableLog = false;
        }

        $isEnable    = $lsr->getWebsiteConfig(LSR::SC_SERVICE_DEBUG, $websiteId) && !$disableLog;
        $timeElapsed = $requestTime->diff($responseTime);

        if ($isEnable) {
            $logger = $this->omniLogger;

            if (str_starts_with($operationName, 'ReplEcomm')) {
                $logger = $this->flatReplicationLogger;
            }
            $logger->debug(
                sprintf(
                    "==== REQUEST ==== %s ==== %s ====",
                    $requestTime->format("m-d-Y H:i:s.u"),
                    $operationName
                )
            );

            if (!empty($this->getClient()->getLastRequest())) {
                $logger->debug($this->formatXML($this->getClient()->getLastRequest()));
            }

            $logger->debug(
                sprintf(
                    "==== RESPONSE ==== %s ==== %s ====",
                    $responseTime->format("m-d-Y H:i:s.u"),
                    $operationName
                )
            );
            $seconds = $timeElapsed->s + $timeElapsed->f;
            $logger->debug(
                sprintf(
                    "==== Time Elapsed ==== %s ==== %s ====",
                    $timeElapsed->format("%i minute(s) " . $seconds . " second(s)"),
                    $operationName
                )
            );

            if (!empty($this->getClient()->getLastResponse())) {
                $logger->debug($this->formatXML($this->getClient()->getLastResponse()));
            }
        }
    }

    /**
     * @param $xmlString
     * @return string
     */
    private function formatXML($xmlString)
    {
        //@codingStandardsIgnoreStart
        $dom = new DOMDocument("1.0");
        //@codingStandardsIgnoreEnd
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput       = true;
        $dom->loadXML($xmlString);

        $this->censorPlanTextForGivenTags(
            $dom,
            ['password', 'Password', 'newPassword', 'oldPassword', 'PasswordResetResult', 'token', 'SecurityToken']
        );
        //@codingStandardsIgnoreLine
        return "\n" . html_entity_decode($dom->saveXML());
    }

    /**
     * Censor all required plain text tags in requests and response
     *
     * @param $dom
     * @param $tagNames
     */
    private function censorPlanTextForGivenTags(&$dom, $tagNames)
    {
        foreach ($tagNames as $tag) {
            $nodes = $dom->getElementsByTagName($tag);

            for ($i = 0; $i < $nodes->length; $i++) {
                $current = $nodes->item($i);

                if ($current->nodeValue) {
                    $current->nodeValue = '****';
                }
            }
        }
    }

    /**
     * Is Tokenized
     *
     * @return false
     */
    protected function isTokenized()
    {
        return false;
    }

    /**
     * Get message value from session
     *
     * @return mixed
     */
    public function getValue()
    {
        $this->session->start();
        return $this->session->getMessage();
    }
}
