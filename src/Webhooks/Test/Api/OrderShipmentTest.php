<?php

namespace Ls\Webhooks\Test\Api;

use Magento\Framework\Webapi\Rest\Request;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Class for testing order shipping endpoint
 */
class OrderShipmentTest extends AbstractWebhookTest
{
    /**
     * @var $product
     */
    private $product;

    /**
     * @var Customer
     */
    private $customer;

    protected function setUp(): void
    {
        parent::setUp();
        // Create product
        $this->product = $this->getOrCreateProduct();

        // Create customer
        $this->customer = $this->getOrCreateCustomer();
    }

    /**
     * Test for the set method in the OrderShipmentInterface
     */
    public function testSetOrderShipment()
    {
        $documentId  = 'DO00' . rand(1, 10000);
        $incrementId = "2000000" . rand(1, 10000);
        // Create order
        $order = $this->getOrCreateOrder($incrementId, $documentId, $this->customer, $this->product, true, false);

        // Bootstrapping Magento
        $objectManager = Bootstrap::getObjectManager();
        $serviceInfo   = [
            'rest' => [
                'resourcePath' => '/V1/ordershipping',
                'httpMethod'   => Request::HTTP_METHOD_POST
            ],
        ];

        $requestData = [
            'OrderId'     => $documentId,
            'ShipmentNo'  => $incrementId,
            'TrackingId'  => 'TRK123',
            'TrackingUrl' => 'http://www.flatrate.com',
            'Provider'    => 'FLATRATE',
            'Service'     => 'FLATRATE',
            'Lines'       => [
                [
                    'LineNo'          => '10000',
                    'ItemId'          => $this->productSku,
                    'VariantId'       => '',
                    'UnitOfMeasureId' => 'PCS',
                    'Quantity'        => 1.0,
                    'Amount'          => $this->product->getPrice(),
                ]
            ]
        ];

        // Using the Magento Web API client to send the request
        $response = $this->_webApiCall($serviceInfo, $requestData);
        if ($response) {
            foreach ($response as $result) {
                $this->assertEquals(true, $result['success']);
            }
        }
    }
}
