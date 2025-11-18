<?php

namespace Ls\Webhooks\Test\Api;

use Magento\Framework\Webapi\Rest\Request;
use Magento\TestFramework\Helper\Bootstrap;

class OrderPaymentTest extends AbstractWebhookBase
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

//        $this->objectManager = Bootstrap::getObjectManager();
//        $this->orderPayment = $this->objectManager->get();
    }

    /**
     * Test for the set method in the OrderPaymentInterface
     */
    public function testSetOrderPaymentWithShipping()
    {
        $documentId  = 'DO00' . rand(1, 10000);
        $incrementId = "2000000" . rand(1, 10000);
        // Create order
        $order = $this->getOrCreateOrder($incrementId, $documentId, $this->customer, $this->product, true, false);
        // Bootstrapping Magento
        
        $serviceInfo   = [
            'rest' => [
                'resourcePath' => '/V1/OrderMessagePayment',
                'httpMethod'   => Request::HTTP_METHOD_POST,
            ],
        ];

        $requestData = [
            'orderPayment' => [
                'OrderId'      => $documentId,
                'Status'       => '0',
                'Amount'       => $order->getGrandTotal(),
                'CurrencyCode' => 'GBP',
                'Token'        => $order->getPayment()->getCcTransId(),
                'AuthCode'     => '',
                'Reference'    => $incrementId,
                'Lines'        => [
                    [
                        'LineNo'          => '10000',
                        'ItemId'          => $this->productSku,
                        'VariantId'       => '',
                        'UnitOfMeasureId' => 'PCS',
                        'Quantity'        => 1.0,
                        'Amount'          => $this->product->getPrice(),
                    ],
                    [
                        'LineNo'          => '20000',
                        'ItemId'          => '66010',
                        'VariantId'       => '',
                        'UnitOfMeasureId' => 'PCS',
                        'Quantity'        => 1.0,
                        'Amount'          => 5.0,
                    ]
                ]
            ]
        ];

        // Using the Magento Web API client to send the request
        $response = $this->_webApiCall($serviceInfo, $requestData, 'rest');
        if ($response) {
            foreach ($response as $result) {
                $this->assertEquals(true, $result['success']);
            }
        }
    }

    /**
     * Test partial invoice
     */
    public function testSetOrderPaymentWithShippingPartial()
    {
        $documentId  = 'DO00' . rand(1, 10000);
        $incrementId = "2000000" . rand(1, 10000);
        // Create order
        $order = $this->getOrCreateOrder($incrementId, $documentId, $this->customer, $this->product, true, false, 2);

        // Bootstrapping Magento
        $objectManager = Bootstrap::getObjectManager();
        $serviceInfo   = [
            'rest' => [
                'resourcePath' => '/V1/OrderMessagePayment',
                'httpMethod'   => Request::HTTP_METHOD_POST
            ],
        ];

        // The input data (this data should align with the actual endpoint requirements)
        $requestData = [
                'orderPayment' => [
                'OrderId'      => $documentId,
                'Status'       => '0',
                'Amount'       => $order->getGrandTotal(),
                'CurrencyCode' => 'GBP',
                'Token'        => $order->getPayment()->getCcTransId(),
                'AuthCode'     => '',
                'Reference'    => $incrementId,
                'Lines'        => [
                    [
                        'LineNo'          => '10000',
                        'ItemId'          => $this->productSku,
                        'VariantId'       => '',
                        'UnitOfMeasureId' => 'PCS',
                        'Quantity'        => 1.0,
                        'Amount'          => $this->product->getPrice(),
                    ],
                    [
                        'LineNo'          => '20000',
                        'ItemId'          => '66010',
                        'VariantId'       => '',
                        'UnitOfMeasureId' => 'PCS',
                        'Quantity'        => 1.0,
                        'Amount'          => 5.0,
                    ]
                ]
            ]
        ];

        // Using the Magento Web API client to send the request
        $response = $this->_webApiCall($serviceInfo, $requestData, 'rest');
        if ($response) {
            foreach ($response as $result) {
                $this->assertEquals(true, $result['success']);
            }
        }
    }

    /**
     * Test card payment with click and collect (Shipment will also be created in it due to click and collect)
     */
    public function testSetOrderPaymentWithClickAndCollect()
    {
        $documentId  = 'DO00' . rand(1, 10000);
        $incrementId = "2000000" . rand(1, 10000);
        // Create order
        $order = $this->getOrCreateOrder($incrementId, $documentId, $this->customer, $this->product, false, false);

        // Bootstrapping Magento
        $objectManager = Bootstrap::getObjectManager();
        $serviceInfo   = [
            'rest' => [
                'resourcePath' => '/V1/OrderMessagePayment',
                'httpMethod'   => Request::HTTP_METHOD_POST
            ],
        ];

        // The input data (this data should align with the actual endpoint requirements)
        $requestData = [
            'orderPayment' => [
                'OrderId'      => $documentId,
                'Status'       => '0',
                'Amount'       => $order->getGrandTotal(),
                'CurrencyCode' => 'GBP',
                'Token'        => $order->getPayment()->getCcTransId(),
                'AuthCode'     => '',
                'Reference'    => $incrementId,
                'Lines'        => [
                    [
                        'LineNo'          => '10000',
                        'ItemId'          => $this->productSku,
                        'VariantId'       => '',
                        'UnitOfMeasureId' => 'PCS',
                        'Quantity'        => 1.0,
                        'Amount'          => $this->product->getPrice(),
                    ],
                    [
                        'LineNo'          => '20000',
                        'ItemId'          => '66010',
                        'VariantId'       => '',
                        'UnitOfMeasureId' => 'PCS',
                        'Quantity'        => 1.0,
                        'Amount'          => 5.0,
                    ]
                ]
            ]
        ];

        // Using the Magento Web API client to send the request
        $response = $this->_webApiCall($serviceInfo, $requestData, 'rest');
        if ($response) {
            foreach ($response as $result) {
                $this->assertEquals(true, $result['success']);
            }
        }
    }
}
