<?php
declare(strict_types=1);

namespace Ls\Webhooks\Observer\Push;

use \Ls\Core\Model\LSR;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ResponseFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Webapi\Rest\Request;

/**
 * Dispatcher for the `ls_push_notification_send` event.
 */
class LsPushNotificationSendObserver implements ObserverInterface
{
    /**
     * API request endpoint
     */
    public const API_REQUEST_ENDPOINT = 'https://onesignal.com/api/v1/';

    /**
     * @var ResponseFactory
     */
    public $responseFactory;

    /**
     * @var ClientFactory
     */
    public $clientFactory;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * @var Json
     */
    public $serializer;

    /**
     * @param LSR $lsr
     * @param ClientFactory $clientFactory
     * @param ResponseFactory $responseFactory
     * @param Json $serializer
     */
    public function __construct(
        LSR $lsr,
        ClientFactory $clientFactory,
        ResponseFactory $responseFactory,
        Json $serializer
    ) {
        $this->lsr             = $lsr;
        $this->clientFactory   = $clientFactory;
        $this->responseFactory = $responseFactory;
        $this->serializer      = $serializer;
    }

    /**
     * Handle the `ls_push_notification_send` event.
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $methodName         = 'notifications';
        $order              = $observer->getEvent()->getOrder();
        $notificationStatus = $observer->getEvent()->getNotificationStatus();
        $storeId            = $observer->getEvent()->getStoreId();
        $receiver           = $observer->getEvent()->getReceiver();
        $ccStoreName        = $observer->getEvent()->getCcStoreName();
        $receiverName       = $receiver->getReceiverName();
        $storeName          = $order->getStore()->getFrontEndName();
        $incrementId        = $order->getDocumentId();

        $headers            = [
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($this->lsr->getRestApiKey($storeId))
        ];
        $response           = $this->doRequest(
            self::API_REQUEST_ENDPOINT . $methodName,
            [
                "app_id"                   => $this->lsr->getAppId($storeId),
                "include_subscription_ids" => [$order->getLsSubscriptionId()],
                "headings"                 => ["en" => "Your $storeName order $incrementId has a new status update"],
                "contents"                 => ["en" => "Hello $receiverName, \n\nOrder Status: $notificationStatus at store $ccStoreName."],
            ],
            Request::HTTP_METHOD_POST,
            $headers
        );

        $responseContent    = $response->getBody()->getContents();
    }

    /**
     * Do API request with provided params
     *
     * @param string $uriEndpoint
     * @param array $params
     * @param string $requestMethod
     * @param array $headers
     * @return Response
     */
    private function doRequest(
        string $uriEndpoint,
        array $params = [],
        string $requestMethod = Request::HTTP_METHOD_GET,
        array $headers = []
    ): Response {
        /** @var Client $client */
        $client  = $this->clientFactory->create(['config' => [
            'base_uri' => self::API_REQUEST_ENDPOINT
        ]]);
        $options = [
            'headers' => $headers,
            'body'    => $this->serializer->serialize($params)
        ];

        try {
            $response = $client->request(
                $requestMethod,
                $uriEndpoint,
                $options
            );
        } catch (GuzzleException $exception) {
            /** @var Response $response */
            $response = $this->responseFactory->create([
                'status' => $exception->getCode(),
                'reason' => $exception->getMessage()
            ]);
        }

        return $response;
    }
}
