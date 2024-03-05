<?php

namespace Ls\Omni\Client;

use DOMDocument;
use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Exception\NavException;
use \Ls\Omni\Exception\NavObjectReferenceNotAnInstanceException;
use \Ls\Omni\Helper\CacheHelper;
use \Ls\Omni\Service\ServiceType;
use \Ls\Omni\Service\Soap\Client as OmniClient;
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
     * @var SessionManagerInterface
     */
    public $session;

    /**
     * @var CacheHelper
     */
    public $cacheHelper;

    /**
     * @param ServiceType $service_type
     */
    public function __construct(
        ServiceType $service_type
    ) {
        $this->service_type  = $service_type;
        $this->objectManager = ObjectManager::getInstance();
        $this->logger        = $this->objectManager->get(OmniLogger::class);
        $this->magentoLogger = $this->objectManager->get(LoggerInterface::class);
        $this->session       = $this->objectManager->get(SessionManagerInterface::class);
        $this->cacheHelper   = $this->objectManager->get(CacheHelper::class);
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
        $header        = self::$header;
        $response      = null;
        $lsr           = $this->objectManager->get("\Ls\Core\Model\LSR");
        if (empty($this->token)) {
            $this->setToken($lsr->getWebsiteConfig(LSR::SC_SERVICE_LS_KEY, $lsr->getWebsiteId()));
        }
        //@codingStandardsIgnoreStart
        $client->setStreamContext(
            stream_context_create(
                ['http' => ['header' => "$header: {$this->token}", 'timeout' => floatval($lsr->getOmniTimeout())]]
            )
        );
        $client->setLocation($client->getWSDL());
        //@codingStandardsIgnoreEnd
        $requestTime = \DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));
        try {
            $response = $client->{$operation_name}($request_input);

            if ($operation_name == 'OrderCreate') {
                $lsr->setLicenseValidity("1");
            }
        } catch (SoapFault $e) {
            $navException = $this->parseException($e);
            $this->magentoLogger->critical($navException);
            if ($e->getMessage() != "") {
                if ($e->faultcode == 's:TransactionCalc' && $operation_name == 'OneListCalculate') {
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
     * @param $operationName
     * @param $requestTime
     * @param $responseTime
     * @return void
     */
    private function debugLog($operationName, $requestTime, $responseTime)
    {
        //@codingStandardsIgnoreStart
        $lsr = $this->objectManager->get("\Ls\Core\Model\LSR");
        //@codingStandardsIgnoreEnd
        try {
            $sessionValue = $this->getValue();
            $disableLog = $operationName == 'Ping' && $sessionValue == null;
        } catch (Exception $e) {
            $disableLog = false;
        }

        $isEnable    = $lsr->getStoreConfig(LSR::SC_SERVICE_DEBUG) && !$disableLog;
        $timeElapsed = $requestTime->diff($responseTime);

        if ($isEnable) {
            $this->logger->debug(
                sprintf(
                    "==== REQUEST ==== %s ==== %s ====",
                    $requestTime->format("m-d-Y H:i:s.u"),
                    $operationName
                )
            );

            if (!empty($this->getClient()->getLastRequest())) {
                $this->logger->debug($this->formatXML($this->getClient()->getLastRequest()));
            }

            $this->logger->debug(
                sprintf(
                    "==== RESPONSE ==== %s ==== %s ====",
                    $responseTime->format("m-d-Y H:i:s.u"),
                    $operationName
                )
            );
            $seconds = $timeElapsed->s + $timeElapsed->f;
            $this->logger->debug(
                sprintf(
                    "==== Time Elapsed ==== %s ==== %s ====",
                    $timeElapsed->format("%i minute(s) " . $seconds . " second(s)"),
                    $operationName
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
