<?php

namespace Ls\Webhooks\Test\Api;

use Magento\Framework\Webapi\Rest\Request;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Test for order status web hooks
 */
class OrderStatusTest extends AbstractWebhookBase
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
     * Test for the order status cancellation
     */
    public function testSetOrderStatusCancellation()
    {
        $documentId  = 'DO00' . rand(1, 10000);
        $incrementId = "2000000" . rand(1, 10000);
        // Create order
        $order = $this->getOrCreateOrder($incrementId, $documentId, $this->customer, $this->product, true, false, 2);

        // Bootstrapping Magento
        $objectManager = Bootstrap::getObjectManager();
        $serviceInfo   = [
            'rest' => [
                'resourcePath' => '/V1/OrderMessageStatusUpdate',
                'httpMethod'   => Request::HTTP_METHOD_POST
            ],
        ];

        $requestData = [
            "orderMessage" => [
                'OrderId'        => $documentId,
                'CardId'         => '',
                'HeaderStatus'   => '',
                'MsgSubject'     => '',
                'MsgDetail'      => '',
                'Lines'          => [
                    [
                        'LineNo'          => '10000',
                        'NewStatus'       => 'CANCELED',
                        'ItemId'          => $this->productSku,
                        'VariantId'       => '',
                        'UnitOfMeasureId' => 'PCS',
                        'Quantity'        => 1.0,
                        'Amount'          => $this->product->getPrice(),
                    ]
                ],
                "orderKOTStatus" => null
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
     * Test for the order status picked\
     */
    public function testSetOrderStatusPicked()
    {
        $documentId  = 'DO00' . rand(1, 10000);
        $incrementId = "2000000" . rand(1, 10000);
        // Create order
        $order = $this->getOrCreateOrder($incrementId, $documentId, $this->customer, $this->product, false, true);

        // Bootstrapping Magento
        $objectManager = Bootstrap::getObjectManager();
        $serviceInfo   = [
            'rest' => [
                'resourcePath' => '/V1/OrderMessageStatusUpdate',
                'httpMethod'   => Request::HTTP_METHOD_POST
            ],
        ];

        $requestData = [
            "orderMessage" => [
                'OrderId'        => $documentId,
                'CardId'         => '',
                'HeaderStatus'   => '',
                'MsgSubject'     => '',
                'MsgDetail'      => '',
                'Lines'          => [
                    [
                        'LineNo'          => '10000',
                        'NewStatus'       => 'PICKED',
                        'ItemId'          => $this->productSku,
                        'VariantId'       => '',
                        'UnitOfMeasureId' => 'PCS',
                        'Quantity'        => 1.0,
                        'Amount'          => $this->product->getPrice(),
                    ]
                ],
                "orderKOTStatus" => null
            ]
        ];

        // Using the Magento Web API client to send the request
        $response = $this->_webApiCall($serviceInfo, $requestData, 'rest');
        $this->assertEquals(is_array($response), true);
        if ($response) {
            foreach ($response as $result) {
                $this->assertEquals(true, $result['success']);
            }
        }
    }

    /**
     * Test for the order status collected
     */
    public function testSetOrderStatusCollected()
    {
        $documentId  = 'DO00' . rand(1, 10000);
        $incrementId = "2000000" . rand(1, 10000);
        // Create order
        $order = $this->getOrCreateOrder($incrementId, $documentId, $this->customer, $this->product, false, true);

        // Bootstrapping Magento
        $objectManager = Bootstrap::getObjectManager();
        $serviceInfo   = [
            'rest' => [
                'resourcePath' => '/V1/OrderMessageStatusUpdate',
                'httpMethod'   => Request::HTTP_METHOD_POST
            ],
        ];

        $requestData = [
            "orderMessage" => [
                'OrderId'        => $documentId,
                'CardId'         => '',
                'HeaderStatus'   => '',
                'MsgSubject'     => '',
                'MsgDetail'      => '',
                'Lines'          => [
                    [
                        'LineNo'          => '10000',
                        'NewStatus'       => 'COLLECTED',
                        'ItemId'          => $this->productSku,
                        'VariantId'       => '',
                        'UnitOfMeasureId' => 'PCS',
                        'Quantity'        => 1.0,
                        'Amount'          => $this->product->getPrice(),
                    ]
                ],
                "orderKOTStatus" => null
            ]
        ];

        // Using the Magento Web API client to send the request
        $response = $this->_webApiCall($serviceInfo, $requestData, 'rest');
        $this->assertEquals(is_array($response), true);
        if ($response) {
            foreach ($response as $result) {
                $this->assertEquals(true, $result['success']);
            }
        }
    }

    /**
     * Test for the order status canceled for credit memo
     */
    public function testSetOrderStatusCreditMemo()
    {
        $documentId  = 'DO00' . rand(1, 10000);
        $incrementId = "2000000" . rand(1, 10000);
        // Create order
        $order = $this->getOrCreateOrder($incrementId, $documentId, $this->customer, $this->product, true, false);

        // Bootstrapping Magento
        $objectManager = Bootstrap::getObjectManager();
        $serviceInfo   = [
            'rest' => [
                'resourcePath' => '/V1/OrderMessagePayment',
                'httpMethod'   => Request::HTTP_METHOD_POST
            ],
        ];

        $requestData = [
            "orderPayment" => [
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

        // Bootstrapping Magento
        $objectManager = Bootstrap::getObjectManager();
        $serviceInfo1   = [
            'rest' => [
                'resourcePath' => '/V1/OrderMessageStatusUpdate',
                'httpMethod'   => Request::HTTP_METHOD_POST
            ],
        ];

        $requestData1 = [
            "orderMessage" =>[
                'OrderId'        => $documentId,
                'CardId'         => '',
                'HeaderStatus'   => '',
                'MsgSubject'     => '',
                'MsgDetail'      => '',
                'Lines'          => [
                    [
                        'LineNo'          => '10000',
                        'NewStatus'       => 'CANCELED',
                        'ItemId'          => $this->productSku,
                        'VariantId'       => '',
                        'UnitOfMeasureId' => 'PCS',
                        'Quantity'        => 1.0,
                        'Amount'          => $this->product->getPrice(),
                    ]
                ],
                "orderKOTStatus" => null
            ]
        ];

        // Using the Magento Web API client to send the request
        $response = $this->_webApiCall($serviceInfo1, $requestData1, 'rest');
        if ($response) {
            foreach ($response as $result) {
                $this->assertEquals(true, $result['success']);
            }
        }
    }
}
