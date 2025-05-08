<?php
namespace Ls\Omni\Model\Central;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Ls\Core\Model\LSR;
use Psr\Log\LoggerInterface;
use function PHPUnit\Framework\isJson;

class GuzzleClient
{
    /**
     * @param LoggerInterface $logger
     * @param LSR $lsr
     */
    public function __construct(
        public LoggerInterface $logger,
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
     * @throws GuzzleException
     */
    public function makeRequest($baseUrl, $action, $method, $type = 'odata', $options = [], $query = [], $data = [])
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];
        try {
            $tenant = $options['tenant'];
            $environmentName = $options['environmentName'];
            if (!empty($options['token'])) {
                $token = $options['token'];
                $headers['Authorization'] = 'Bearer ' . $token;
            }

            $client = new Client([
                'base_uri' => $baseUrl,
                'timeout'  => 10.0,
                'headers'  => $headers
            ]);
            $type = $type == 'odata' ? 'ODataV4' : 'WS/Codeunit';

            $endpoint = 'V2.0/' . $tenant . '/' . $environmentName . '/' . $type . '/' . $action;

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
                $body = $response->getBody()->getContents();

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
