<?php

namespace Ls\Omni\Client;

use DOMDocument;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Exception\NavException;
use \Ls\Omni\Exception\NavObjectReferenceNotAnInstanceException;
use \Ls\Omni\Service\ServiceType;
use \Ls\Omni\Service\Soap\Client as OmniClient;
use \Ls\Replication\Logger\OmniLogger;
use Magento\Framework\App\ObjectManager;
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
     * @param ServiceType $service_type
     */
    public function __construct(ServiceType $service_type)
    {
        $this->service_type  = $service_type;
        $this->objectManager = ObjectManager::getInstance();
        $this->logger        = $this->objectManager->get(OmniLogger::class);
        $this->magentoLogger = $this->objectManager->get(LoggerInterface::class);
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
        $requestTime = \DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));
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
        $responseTime = \DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));
        $this->debugLog($operation_name, $requestTime, $responseTime);
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
     * Log request, response and time elapsed
     *
     * @param $operation_name
     * @param $requestTime
     * @param $responseTime
     */
    private function debugLog($operation_name, $requestTime, $responseTime)
    {
        //@codingStandardsIgnoreStart
        $lsr = $this->objectManager->get("\Ls\Core\Model\LSR");
        //@codingStandardsIgnoreEnd
        $isEnable = $lsr->getStoreConfig(LSR::SC_SERVICE_DEBUG);
        $timeElapsed = $requestTime->diff($responseTime);

        if ($isEnable) {
            $this->logger->debug(
                sprintf(
                    "==== REQUEST ==== %s ==== %s ====",
                    $requestTime->format("m-d-Y H:i:s.u"),
                    $operation_name
                )
            );

            if (!empty($this->getClient()->getLastRequest())) {
                $this->logger->debug($this->formatXML($this->getClient()->getLastRequest()));
            }

            $this->logger->debug(
                sprintf(
                    "==== RESPONSE ==== %s ==== %s ====",
                    $responseTime->format("m-d-Y H:i:s.u"),
                    $operation_name
                )
            );
            $seconds = $timeElapsed->s+$timeElapsed->f;
            $this->logger->debug(
                sprintf(
                    "==== Time Elapsed ==== %s ==== %s ====",
                    $timeElapsed->format("%i minute(s) ". $seconds." second(s)"),
                    $operation_name
                )
            );

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

        $this->censorPlanTextForGivenTags(
            $dom,
            ['password', 'Password', 'newPassword', 'oldPassword', 'PasswordResetResult', 'token']
        );

        return "\n" . $dom->saveXML();
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

    protected function isTokenized()
    {
        return false;
    }
}
