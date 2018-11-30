<?php

namespace Ls\Omni\Client;

use Ls\Omni\Exception\TokenNotFoundException;
use Ls\Omni\Exception\NavException;
use Ls\Omni\Exception\NavObjectReferenceNotAnInstanceException;
use Ls\Omni\Service\ServiceType;
use Ls\Omni\Service\Soap\Client as OmniClient;
use Ls\Core\Model\LSR;
abstract class AbstractOperation implements OperationInterface
{
    /** @var string  */
    private static $header = 'LSRETAIL-TOKEN';

    /** @var  ServiceType */
    protected $service_type;

    /** @var string */
    protected $token = NULL;

    /** @var $service_type ServiceType */
    protected $logger;

    /** @var \Zend\Log\Writer\Stream  */
    protected $writer;

    /** @var \Psr\Log\LoggerInterface  */
    protected $magentoLogger;

    /** @var $objectManager \Magento\Framework\App\ObjectManager */
    protected $objectManager;


    /**
     * AbstractOperation constructor.
     * @param ServiceType $service_type
     */
    public function __construct(ServiceType $service_type)
    {
        $this->service_type = $service_type;
        $this->writer = new \Zend\Log\Writer\Stream(BP . '/var/log/omniclient.log');
        $this->logger = new \Zend\Log\Logger();
        $this->logger->addWriter($this->writer);
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->magentoLogger = $this->objectManager->get('\Psr\Log\LoggerInterface');
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
    protected function isTokenized()
    {
        return FALSE;
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
     * @param $operation_name
     *
     * @return ResponseInterface
     * @throws TokenNotFoundException
     */
    protected function makeRequest($operation_name)
    {
        $request_input = $this->getOperationInput();
        $client = $this->getClient();
        $header = self::$header;
        $client->setStreamContext(stream_context_create(['http' => ['header' => "$header: {$this->token}"]]));
        try {
            $response = $client->{$operation_name}($request_input);
        } catch (\SoapFault $e) {
            $navException = $this->parseException($e);
            $this->magentoLogger->critical($navException);
            $response = NULL;
        }
        $this->debugLog($operation_name);
        return $response;
    }

    /**
     * @return OmniClient
     */
    abstract function getClient();

    /**
     * @param $xmlString
     * @return string
     */
    private function formatXML($xmlString)
    {
        $dom = new \DOMDocument("1.0");
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
        $lsr=$this->objectManager->get("\Ls\Core\Model\LSR");
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
    protected function parseException(\SoapFault $exception)
    {
        $navException = new NavException($exception->getMessage(), 1, $exception);
        if (strpos($exception->getMessage(), "Object reference not set to an instance of an object") !== -1) {
            $navException = new NavObjectReferenceNotAnInstanceException($exception->getMessage(), 1, $exception);
        }
        return $navException;
    }
}
