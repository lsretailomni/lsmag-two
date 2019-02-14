<?php

namespace Ls\Omni\Client;

use \Ls\Omni\Exception\TokenNotFoundException;
use \Ls\Omni\Exception\NavException;
use \Ls\Omni\Exception\NavObjectReferenceNotAnInstanceException;
use \Ls\Omni\Service\ServiceType;
use \Ls\Omni\Service\Soap\Client as OmniClient;
use \Ls\Core\Model\LSR;

/**
 * Class AbstractOperation
 * @package Ls\Omni\Client
 */
abstract class AbstractOperation implements OperationInterface
{
    /** @var string  */
    private static $header = 'LSRETAIL-TOKEN';

    /** @var  ServiceType */
    public $service_type;

    /** @var string */
    public $token = null;

    /** @var $service_type ServiceType */
    public $logger;

    /** @var \Zend\Log\Writer\Stream  */
    public $writer;

    /** @var \Psr\Log\LoggerInterface  */
    public $magentoLogger;

    /** @var $objectManager \Magento\Framework\App\ObjectManager */
    public $objectManager;

    /*** @var Zend\Log\Writer\Stream  */
    public $zendWriter;

    /**
     * AbstractOperation constructor.
     * @param ServiceType $service_type
     */
    public function __construct(ServiceType $service_type)
    {
        $this->service_type = $service_type;
        //@codingStandardsIgnoreStart
        $this->writer = new \Zend\Log\Writer\Stream(BP . '/var/log/omniclient.log');
        $this->logger = new \Zend\Log\Logger();
        $this->logger->addWriter($this->writer);
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
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
    protected function isTokenized()
    {
        return false;
    }
    // @codingStandardsIgnoreEnd

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
     * @param $operation_name
     *
     * @return ResponseInterface
     * @throws TokenNotFoundException
     */
    public function makeRequest($operation_name)
    {
        $request_input = $this->getOperationInput();
        $client = $this->getClient();
        $header = self::$header;
        //@codingStandardsIgnoreStart
        $client->setStreamContext(stream_context_create(['http' => ['header' => "$header: {$this->token}"]]));
        //@codingStandardsIgnoreEnd
        try {
            $response = $client->{$operation_name}($request_input);
        } catch (\SoapFault $e) {
            $navException = $this->parseException($e);
            $this->magentoLogger->critical($navException);
            $response = null;
        }
        $this->debugLog($operation_name);
        return $response;
    }

    /**
     * @return OmniClient
     */
    abstract public function getClient();

    /**
     * @param $xmlString
     * @return string
     */
    private function formatXML($xmlString)
    {
        //@codingStandardsIgnoreStart
        $dom = new \DOMDocument("1.0");
        //@codingStandardsIgnoreEnd
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xmlString);
        return "\n" . $dom->saveXML();
    }

    /**
     * @param $operation_name
     */
    private function debugLog($operation_name)
    {
        //@codingStandardsIgnoreStart
        $lsr=$this->objectManager->get("\Ls\Core\Model\LSR");
        //@codingStandardsIgnoreEnd
        $isEnable=$lsr->getStoreConfig(LSR::SC_SERVICE_DEBUG);
        if ($isEnable) {
            $this->logger->debug("==== REQUEST == " . date("Y-m-d H:i:s O") . " == " . $operation_name . " ====");
            $this->logger->debug($this->formatXML($this->getClient()->getLastRequest()));
            $this->logger->debug("==== RESPONSE == " . date("Y-m-d H:i:s O") . " == " . $operation_name . " ====");
            $this->logger->debug($this->formatXML($this->getClient()->getLastResponse()));
        }
    }

    /**
     * @param \SoapFault $exception
     * @return NavException|NavObjectReferenceNotAnInstanceException
     */
    public function parseException(\SoapFault $exception)
    {
        //@codingStandardsIgnoreStart
        $navException = new NavException($exception->getMessage(), 1, $exception);
        if (strpos($exception->getMessage(), "Object reference not set to an instance of an object") !== -1) {
            $navException = new NavObjectReferenceNotAnInstanceException($exception->getMessage(), 1, $exception);
        }
        //@codingStandardsIgnoreEnd
        return $navException;
    }
}
