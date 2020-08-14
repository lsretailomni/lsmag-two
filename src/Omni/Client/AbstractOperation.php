<?php

namespace Ls\Omni\Client;

use DOMDocument;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Exception\NavException;
use \Ls\Omni\Exception\NavObjectReferenceNotAnInstanceException;
use \Ls\Omni\Service\ServiceType;
use \Ls\Omni\Service\Soap\Client as OmniClient;
use Magento\Framework\App\ObjectManager;
use SoapFault;
use Laminas\Log\Logger;
use Laminas\Log\Writer\Stream;

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
     * @var Logger
     */
    public $logger;

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
     * AbstractOperation constructor.
     * @param ServiceType $service_type
     */
    public function __construct(ServiceType $service_type)
    {
        $this->service_type = $service_type;
        //@codingStandardsIgnoreStart
        $this->writer = new Stream(BP . '/var/log/omniclient.log');
        $this->logger = new Logger();
        $this->logger->addWriter($this->writer);
        $this->objectManager = ObjectManager::getInstance();
        $this->magentoLogger = $this->objectManager->get('\Psr\Log\LoggerInterface');
        //@codingStandardsIgnoreEnd
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
     */
    public function makeRequest($operation_name)
    {
        $request_input = $this->getOperationInput();
        $client        = $this->getClient();
        $header        = self::$header;
        $response      = null;
        $lsr           = $this->objectManager->get("\Ls\Core\Model\LSR");
        if (empty($this->token)) {
            $this->setToken($lsr->getStoreConfig(LSR::SC_SERVICE_LS_KEY));
        }
        //@codingStandardsIgnoreStart
        $client->setStreamContext(
            stream_context_create(
                ['http' => ['header' => "$header: {$this->token}", 'timeout' => floatval($lsr->getOmniTimeout())]]
            )
        );
        //@codingStandardsIgnoreEnd
        try {
            $response = $client->{$operation_name}($request_input);
        } catch (SoapFault $e) {
            $navException = $this->parseException($e);
            $this->magentoLogger->critical($navException);
            if ($e->getMessage() != "") {
                if ($e->faultcode == 's:TransactionCalc' && $operation_name == 'OneListCalculate') {
                    $response = $e->getMessage();
                }
            } else {
                $response = null;
            }
        }
        $this->debugLog($operation_name);
        return $response;
    }
    // @codingStandardsIgnoreEnd

    /**
     * @return OmniClient
     */
    abstract public function getClient();

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
     * @param $operation_name
     */
    private function debugLog($operation_name)
    {
        //@codingStandardsIgnoreStart
        $lsr = $this->objectManager->get("\Ls\Core\Model\LSR");
        //@codingStandardsIgnoreEnd
        $isEnable = $lsr->getStoreConfig(LSR::SC_SERVICE_DEBUG);
        if ($isEnable) {
            $this->logger->debug("==== REQUEST == " . date("Y-m-d H:i:s O") . " == " . $operation_name . " ====");
            if (!empty($this->getClient()->getLastRequest())) {
                $this->logger->debug($this->formatXML($this->getClient()->getLastRequest()));
            }

            $this->logger->debug("==== RESPONSE == " . date("Y-m-d H:i:s O") . " == " . $operation_name . " ====");
            if (!empty($this->getClient()->getLastResponse())) {
                $this->logger->debug($this->formatXML($this->getClient()->getLastResponse()));
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

        return "\n" . $dom->saveXML();
    }

    protected function isTokenized()
    {
        return false;
    }
}
