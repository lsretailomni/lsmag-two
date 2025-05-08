<?php
namespace Ls\Omni\Model\Central;

use Ls\Core\Model\LSR;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;

class TokenRequestService
{
    /**
     * @var Curl
     */
    public $curl;

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @param Curl $curl
     * @param LoggerInterface $logger
     */
    public function __construct(Curl $curl, LoggerInterface $logger)
    {
        $this->curl = $curl;
        $this->logger = $logger;
    }

    /**
     * Request token from microsoft
     *
     * @param string $tenant
     * @param string $clientId
     * @param string $clientSecret
     * @return array
     */
    public function requestToken($tenant, $clientId, $clientSecret)
    {
        $tokenEndpoint = sprintf(LSR::TOKEN_ENDPOINT, $tenant);

        $postData = [
            'grant_type'    => LSR::TOKEN_GRANT_TYPE,
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'scope'         => LSR::TOKEN_SCOPE
        ];

        $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
        $this->curl->setHeaders($headers);

        $postBody = http_build_query($postData);

        try {
            $this->curl->post($tokenEndpoint, $postBody);
        } catch (\Exception $e) {
            $this->logger->debug(sprintf('Unable to generate Token getting error : %s', $e->getMessage()));
        }

        $response = $this->curl->getBody();

        return $response ? json_decode($response, true) : '';
    }
}
