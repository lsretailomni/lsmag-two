<?php
declare(strict_types=1);

namespace Ls\Omni\Model\Central;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use \Ls\Core\Model\LSR;
use \Ls\Replication\Logger\FlatReplicationLogger;
use \Ls\Replication\Logger\OmniLogger;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class GuzzleClient
{
    /**
     * @var mixed
     */
    public $currentLogger = null;

    /**
     * @param LoggerInterface $logger
     * @param OmniLogger $omniLogger
     * @param FlatReplicationLogger $flatReplicationLogger
     * @param LSR $lsr
     */
    public function __construct(
        public LoggerInterface $logger,
        public OmniLogger $omniLogger,
        public FlatReplicationLogger $flatReplicationLogger,
        public LSR $lsr
    ) {
    }

    /**
     * Make a guzzle request
     *
     * @param string $baseUrl
     * @param string $action
     * @param string $method
     * @param string $type
     * @param array $options
     * @param array $query
     * @param array $data
     * @return mixed|null
     * @throws GuzzleException|NoSuchEntityException
     */
    public function makeRequest($baseUrl, $action, $method, $type = 'odata', $options = [], $query = [], $data = [])
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];
        $timeout = $this->lsr->getOmniTimeout();
        try {
            $centralType = $options['centralType'];
            $tenant = $options['tenant'];
            $environmentName = $options['environmentName'];
            if (!empty($options['token'])) {
                $token = $options['token'];
                $headers['Authorization'] = $centralType == '1' ? 'Bearer ' . $token : 'Basic ' . $token;
            }

            if (str_starts_with($action, 'ODataRequest_')) {
                $this->currentLogger = $this->flatReplicationLogger;
            } else {
                $this->currentLogger = $this->omniLogger;
            }

            $handlerStack = HandlerStack::create();
            if ($this->lsr->getWebsiteConfig(LSR::SC_SERVICE_DEBUG, $this->lsr->getCurrentWebsiteId())) {
                $handlerStack->push($this->getLoggingMiddleware());
            }

            $client = new Client([
                'base_uri' => $baseUrl,
                'timeout'  => $timeout,
                'headers'  => $headers,
                'handler'  => $handlerStack
            ]);
            $type = $type == 'odata' ? 'ODataV4' : 'WS/Codeunit';

            $endpoint = $centralType == '1' ?
                'V2.0/' . $tenant . '/' . $environmentName . '/' . $type . '/' . $action :
                $type . '/' . $action;
            if ($method == 'POST') {
                if (!empty($query)) {
                    $payload['query'] = $query;
                }

                if (!empty($data)) {
                    $payload['json'] = $data;
                }

                $response = $client->post(
                    $endpoint,
                    !empty($payload) ? $payload: []
                );
            } else {
                $response = $client->get(
                    $endpoint,
                    !empty($query) ? ['query' => $query] : []
                );
            }

            if ($response->getStatusCode() == 200) {
                $body = (string) $response->getBody();

                if ($this->isJson($body)) {
                    $outer = json_decode($body, true);
                    return json_decode($outer['value'], true);
                } else {
                    return $body;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Guzzle Business Central Error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Get middleware for guzzle client
     *
     * @return callable
     */
    private function getLoggingMiddleware(): callable
    {
        return function (callable $handler) {
            return function (
                RequestInterface $request,
                array $options
            ) use ($handler) {
                $requestTime = \DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));
                $this->currentLogger->debug(
                    sprintf(
                        "==== REQUEST ==== %s ==== %s ====",
                        $requestTime->format("m-d-Y H:i:s.u"),
                        $request->getMethod() . ' ' . $request->getUri()
                    )
                );
                $body = (string) $request->getBody();
                if (!empty($body)) {
                    $this->currentLogger->debug(sprintf('Request Body: %s ', $body));
                }

                return $handler($request, $options)->then(
                    function (ResponseInterface $response) use ($requestTime, $request) {
                        $responseTime = \DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));
                        $this->currentLogger->debug(
                            sprintf(
                                "==== RESPONSE ==== %s ==== %s ==== %s",
                                $responseTime->format("m-d-Y H:i:s.u"),
                                $request->getMethod() . ' ' . $request->getUri(),
                                $response->getStatusCode()
                            )
                        );
                        $timeElapsed = $requestTime->diff($responseTime);
                        $seconds = $timeElapsed->s + $timeElapsed->f;
                        $this->omniLogger->debug(
                            sprintf(
                                "==== Time Elapsed ==== %s ====  ====",
                                $timeElapsed->format("%i minute(s) " . $seconds . " second(s)")
                            )
                        );

                        $body = (string) $response->getBody();
                        if (!empty($body)) {
                            if ($this->isJson($body)) {
                                $outer = json_decode($body, true);
                                $decoded = isset($outer['value']) ? json_decode($outer['value'], true) : $outer;
                                $prettyJson = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                                $this->currentLogger->debug("Response Body:\n" . $prettyJson);
                            } else {
                                $this->currentLogger->debug(sprintf('Response Body: %s', $body));
                            }
                        }

                        return $response;
                    }
                );
            };
        };
    }

    /**
     * Check if the response is a valid json object
     *
     * @param string $string
     * @return bool
     */
    public function isJson($string)
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
