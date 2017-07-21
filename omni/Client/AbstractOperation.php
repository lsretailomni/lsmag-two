<?php
namespace Ls\Omni\Client;

use Ls\Omni\Service\ServiceType;
use Ls\Omni\Service\Soap\Client as OmniClient;

abstract class AbstractOperation implements IOperation
{
    /** @var  ServiceType */
    protected $service_type;
    protected $logger;
    protected $writer;

    public function __construct ( ServiceType $service_type ) {
        $this->service_type = $service_type;
        $this->writer = new \Zend\Log\Writer\Stream(BP . '/var/log/omniclient.log');
        $this->logger = new \Zend\Log\Logger();
        $this->logger->addWriter($this->writer);
    }

    /**
     * @return ServiceType
     */
    public function getServiceType () {
        return $this->service_type;
    }

    /**
     * @return OmniClient
     */
    abstract function getClient ();

    private function formatXML($xmlString) {
        $dom = new \DOMDocument("1.0");
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xmlString);
        return "\n".$dom->saveXML();
    }

    private function debugLog($operation_name) {
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        /** @return \Magento\Framework\App\State */
        $state = $om->get('Magento\Framework\App\State');
        /** @var bool $isDeveloperMode */
        $isDeveloperMode = \Magento\Framework\App\State::MODE_DEVELOPER === $state->getMode();

        if ($isDeveloperMode) {
            $this->logger->debug("==== REQUEST == ".date("Y-m-d H:i:s O")." == ".$operation_name." ====");
            $this->logger->debug($this->formatXML($this->getClient()->getLastRequest()));
            $this->logger->debug("==== RESPONSE == ".date("Y-m-d H:i:s O")." == ".$operation_name." ====");
            $this->logger->debug($this->formatXML($this->getClient()->getLastResponse()));
        }
    }

    protected function makeRequest ( $operation_name ) {
        $request_input = $this->getOperationInput();
        $client = $this->getClient();
        // TODO: add error handling, maybe parse Nav errors into new NavException?
        $response = $client->{$operation_name}( $request_input );
        $this->debugLog($operation_name);
        return $response;
    }
}
