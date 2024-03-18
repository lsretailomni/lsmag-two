<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfOneListItem;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfOrderPayment;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\ListType;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\OrderType;
use \Ls\Omni\Client\Ecommerce\Entity\OneList;
use \Ls\Omni\Client\Ecommerce\Entity\OneListCalculate;
use \Ls\Omni\Client\Ecommerce\Entity\OneListItem;
use \Ls\Omni\Client\Ecommerce\Entity\Order;
use \Ls\Omni\Client\Ecommerce\Entity\OrderLine;
use \Ls\Omni\Client\Ecommerce\Entity\OrderPayment;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntry;
use \Ls\Omni\Exception\InvalidEnumException;

/**
 * It will cover all the methods used for Order Creation Cycle
 */
class OrderEditMethodsTest extends OmniClientSetupTest
{
    /**
     * Get One List
     *
     * @return mixed
     * @throws InvalidEnumException
     */
    public function getOneList($cardId = '')
    {
        $listItems = new OneListItem();
        $listItems
            ->setItemId($this->getEnvironmentVariableValueGivenName('ITEM_ID'))
            ->setVariantId($this->getEnvironmentVariableValueGivenName('VARIANT_ID'))
            ->setQuantity(1);
        $itemsArray = new ArrayOfOneListItem();
        $itemsArray->setOneListItem($listItems);
        $oneListRequest = new OneList();
        $oneListRequest
            ->setItems($itemsArray)
            ->setCardId($cardId)
            ->setStoreId($this->getEnvironmentVariableValueGivenName('STORE_ID'))
            ->setListType(ListType::BASKET);
        $param = [
            'oneList' => $oneListRequest,
            'calculate' => true
        ];

        return $this->client->OneListSave($param);
    }



    /**
     * Edit customer order
     * Type - ClickAndCollect
     * User - Member
     * PaymentLine - Cash
     * @depends testOneListSaveBasket
     */
    public function testOrderEdit()
    {
        $response       = $this->getOneList($this->getEnvironmentVariableValueGivenName('CARD_ID'));
        $oneListRequest = $response->getResult();
        $entity         = new OneListCalculate();
        $entity->setOneList($oneListRequest);
        $response = $this->client->OneListCalculate($entity);
        $result   = $response->getResult();
        $this->assertInstanceOf(Order::class, $result);
        $orderPayment = new OrderPayment();
        $orderPayment->setCurrencyFactor(1)
            ->setAmount($result->getTotalAmount())
            ->setLineNumber('1')
            ->setExternalReference('TEST0012345')
            ->setTenderType($this->getEnvironmentVariableValueGivenName('CASH_TENDER_TYPE'));
        $orderPayments = new ArrayOfOrderPayment();
        $orderPayments->setOrderPayment([$orderPayment]);
        $result->setOrderPayments($orderPayments);
        $result->setOrderType(OrderType::CLICK_AND_COLLECT);
        $result->setId('test' . substr(preg_replace("/[^A-Za-z0-9 ]/", '', $result->getId()), 0, 10));
        // Order creation request
        $paramOrderCreate  = [
            'request' => $result
        ];
        $responseOrder     = $this->client->OrderEdit($paramOrderCreate);
        $resultOrderCreate = $responseOrder->getResult();
        $this->assertInstanceOf(SalesEntry::class, $resultOrderCreate);
        $this->assertTrue(property_exists($resultOrderCreate, 'Id'));
        $this->assertTrue(property_exists($resultOrderCreate, 'CardId'));
        $this->assertTrue(property_exists($resultOrderCreate, 'ExternalId'));
        $this->assertTrue(property_exists($resultOrderCreate, 'StoreId'));
        $this->assertTrue(property_exists($resultOrderCreate, 'TotalAmount'));
        $this->assertTrue(property_exists($resultOrderCreate, 'TotalDiscount'));
        $this->assertTrue(property_exists($resultOrderCreate, 'TotalNetAmount'));
        $this->assertTrue(property_exists($resultOrderCreate, 'Status'));
        $this->assertTrue(property_exists($resultOrderCreate, 'Payments'));
        $this->assertTrue(property_exists($resultOrderCreate, 'Lines'));
    }
}
