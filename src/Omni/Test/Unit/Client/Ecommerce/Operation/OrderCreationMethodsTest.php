<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\Entity\Address;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfInventoryRequest;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfInventoryResponse;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfOneList;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfOneListItem;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfOrderLine;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfOrderPayment;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\ListType;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\OrderType;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\PaymentType;
use \Ls\Omni\Client\Ecommerce\Entity\InventoryRequest;
use \Ls\Omni\Client\Ecommerce\Entity\LoyItem;
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
class OrderCreationMethodsTest extends OmniClientSetupTest
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
     * Lookup Item
     */
    public function testItemGetbyId()
    {
        $param    = [
            'itemId' => $this->getEnvironmentVariableValueGivenName('ITEM_ID'),
            'storeId' => $this->getEnvironmentVariableValueGivenName('STORE_ID'),
        ];
        $response = $this->executeMethod("ItemGetbyId", $param);
        $result = $response ? $response->getResult() : null;
        $this->assertInstanceOf(LoyItem::class, $result);
    }

    /**
     * Get stock status of an item from specific store
     */
    public function testItemsInStockGet()
    {
        $param    = [
            'itemId' => $this->getEnvironmentVariableValueGivenName('ITEM_ID'),
            'variantId' => $this->getEnvironmentVariableValueGivenName('VARIANT_ID'),
            'storeId' => $this->getEnvironmentVariableValueGivenName('STORE_ID'),
        ];
        $response = $this->executeMethod("ItemsInStockGet", $param);
        $result = $response ? $response->getResult() : null;
        $this->assertInstanceOf(ArrayOfInventoryResponse::class, $result);
        foreach ($result as $inventoryResponse) {
            $this->assertEquals(
                $this->getEnvironmentVariableValueGivenName('ITEM_ID'),
                $inventoryResponse->getItemId()
            );
            $this->assertEquals(
                $this->getEnvironmentVariableValueGivenName('VARIANT_ID'),
                $inventoryResponse->getVariantId()
            );
            $this->assertEquals(
                $this->getEnvironmentVariableValueGivenName('STORE_ID'),
                $inventoryResponse->getStoreId()
            );
            $this->assertTrue(property_exists($inventoryResponse, 'QtyInventory'));
            $this->assertTrue(is_string($inventoryResponse->getQtyInventory()));
        }
    }

    /**
     * Get stock status of an item from all stores
     * If storeId is empty, only store that are marked in LS Nav/Central with
     * check box Loyalty or Mobile checked (Omni Section) will be returned
     */
    public function testItemsInStockGetAllStores()
    {
        $param    = [
            'itemId' => $this->getEnvironmentVariableValueGivenName('ITEM_ID'),
            'variantId' => $this->getEnvironmentVariableValueGivenName('VARIANT_ID'),
            'storeId' => '',
        ];
        $response = $this->executeMethod("ItemsInStockGet", $param);
        $result = $response ? $response->getResult() : null;
        $this->assertInstanceOf(ArrayOfInventoryResponse::class, $result);
        foreach ($result as $inventoryResponse) {
            $this->assertEquals(
                $this->getEnvironmentVariableValueGivenName('ITEM_ID'),
                $inventoryResponse->getItemId()
            );
            $this->assertEquals(
                $this->getEnvironmentVariableValueGivenName('VARIANT_ID'),
                $inventoryResponse->getVariantId()
            );
            $this->assertNotNull($inventoryResponse->getStoreId());
            $this->assertTrue(property_exists($inventoryResponse, 'QtyInventory'));
            $this->assertTrue(is_string($inventoryResponse->getQtyInventory()));
        }
    }

    /**
     * Get stock status for list of items from one store
     */
    public function testItemsInStoreGetSingleStore()
    {
        $inventoryRequest = new InventoryRequest();
        $inventoryRequest->setItemId($this->getEnvironmentVariableValueGivenName('ITEM_ID'));
        $inventoryRequest->setVariantId($this->getEnvironmentVariableValueGivenName('VARIANT_ID'));
        $inventoryArrayRequest = new ArrayOfInventoryRequest();
        $inventoryArrayRequest->setInventoryRequest([$inventoryRequest]);
        $param    = [
            'storeId' => $this->getEnvironmentVariableValueGivenName('STORE_ID'),
            'items' => $inventoryArrayRequest
        ];
        $response = $this->executeMethod("ItemsInStoreGet", $param);
        $result = $response ? $response->getResult() : null;
        $this->assertInstanceOf(ArrayOfInventoryResponse::class, $result);
        foreach ($result as $inventoryResponse) {
            $this->assertEquals(
                $this->getEnvironmentVariableValueGivenName('ITEM_ID'),
                $inventoryResponse->getItemId()
            );
            $this->assertEquals(
                $this->getEnvironmentVariableValueGivenName('VARIANT_ID'),
                $inventoryResponse->getVariantId()
            );
            $this->assertEquals(
                $this->getEnvironmentVariableValueGivenName('STORE_ID'),
                $inventoryResponse->getStoreId()
            );
            $this->assertTrue(property_exists($inventoryResponse, 'QtyInventory'));
            $this->assertTrue(is_string($inventoryResponse->getQtyInventory()));
        }
    }

    /**
     * Get stock status for list of items from all stores
     */
    public function testItemsInStoreGetAllStores()
    {
        $inventoryRequest = new InventoryRequest();
        $inventoryRequest->setItemId($this->getEnvironmentVariableValueGivenName('ITEM_ID'));
        $inventoryRequest->setVariantId($this->getEnvironmentVariableValueGivenName('VARIANT_ID'));
        $inventoryArrayRequest = new ArrayOfInventoryRequest();
        $inventoryArrayRequest->setInventoryRequest([$inventoryRequest]);
        $param    = [
            'storeId' => '',
            'items' => $inventoryArrayRequest
        ];
        $response = $this->executeMethod("ItemsInStoreGet", $param);
        $result = $response ? $response->getResult() : null;
        $this->assertInstanceOf(ArrayOfInventoryResponse::class, $result);
        foreach ($result as $inventoryResponse) {
            $this->assertEquals(
                $this->getEnvironmentVariableValueGivenName('ITEM_ID'),
                $inventoryResponse->getItemId()
            );
            $this->assertEquals(
                $this->getEnvironmentVariableValueGivenName('VARIANT_ID'),
                $inventoryResponse->getVariantId()
            );
            $this->assertNotNull($inventoryResponse->getStoreId());
            $this->assertTrue(property_exists($inventoryResponse, 'QtyInventory'));
            $this->assertTrue(is_string($inventoryResponse->getQtyInventory()));
        }
    }

    /**
     * Calculates OneList Basket Object and returns Order Object
     * @throws InvalidEnumException
     */
    public function testOneListCalculate()
    {
        $oneListRequest = new OneList();
        $listItems      = new OneListItem();
        $listItems->setItemId($this->getEnvironmentVariableValueGivenName('ITEM_ID'));
        $listItems->setVariantId($this->getEnvironmentVariableValueGivenName('VARIANT_ID'));
        $listItems->setQuantity(1);
        $itemsArray = new ArrayOfOneListItem();
        $itemsArray->setOneListItem($listItems);
        $oneListRequest->setItems($itemsArray);
        $oneListRequest->setCardId($this->getEnvironmentVariableValueGivenName('CARD_ID'));
        $oneListRequest->setStoreId($this->getEnvironmentVariableValueGivenName('STORE_ID'));
        $oneListRequest->setListType(ListType::BASKET);
        $entity = new OneListCalculate();
        $entity->setOneList($oneListRequest);
        $response = $this->executeMethod("OneListCalculate", $entity);
        $result = $response ? $response->getResult() : null;
        $this->assertInstanceOf(Order::class, $result);
        $this->assertEquals($this->getEnvironmentVariableValueGivenName('STORE_ID'), $result->getStoreId());
        $this->assertEquals($this->getEnvironmentVariableValueGivenName('CARD_ID'), $result->getCardId());
        $this->assertNotNull($result->getTotalAmount());
        $this->assertNotNull($result->getTotalNetAmount());
        $this->assertTrue(is_string($result->getOrderType()));
        $this->assertEquals(OrderType::SALE, $result->getOrderType());
        $this->assertInstanceOf(ArrayOfOrderLine::class, $result->getOrderLines());
    }

    /**
     * Save Basket type one list
     * @throws InvalidEnumException
     */
    public function testOneListSaveBasket()
    {
        $listItems = new OneListItem();
        $listItems->setItemId($this->getEnvironmentVariableValueGivenName('ITEM_ID'));
        $listItems->setVariantId($this->getEnvironmentVariableValueGivenName('VARIANT_ID'));
        $listItems->setQuantity(1);
        $itemsArray = new ArrayOfOneListItem();
        $itemsArray->setOneListItem($listItems);
        $oneListRequest = new OneList();
        $oneListRequest->setItems($itemsArray);
        $oneListRequest->setCardId($this->getEnvironmentVariableValueGivenName('CARD_ID'));
        $oneListRequest->setStoreId($this->getEnvironmentVariableValueGivenName('STORE_ID'));
        $oneListRequest->setListType(ListType::BASKET);
        $param    = [
            'oneList' => $oneListRequest,
            'calculate' => true
        ];
        $response = $this->executeMethod("OneListSave", $param);
        $oneList = $response ? $response->getResult() : null;
        $this->assertInstanceOf(OneList::class, $oneList);
        $this->assertEquals($this->getEnvironmentVariableValueGivenName('CARD_ID'), $oneList->getCardId());
        $this->assertTrue(property_exists($oneList, 'Id'));
        $this->assertTrue(property_exists($oneList, 'ListType'));
        $this->assertTrue(property_exists($oneList, 'CreateDate'));
        $this->assertTrue(property_exists($oneList, 'StoreId'));
        $this->assertTrue(property_exists($oneList, 'TotalAmount'));
        $this->assertTrue(property_exists($oneList, 'TotalDiscAmount'));
        $this->assertTrue(property_exists($oneList, 'TotalNetAmount'));
        $this->assertTrue(property_exists($oneList, 'TotalTaxAmount'));
    }

    /**
     * Save Basket type one list for Guest
     * @throws InvalidEnumException
     */
    public function testOneListSaveBasketGuest()
    {
        $listItems = new OneListItem();
        $listItems->setItemId($this->getEnvironmentVariableValueGivenName('ITEM_ID'));
        $listItems->setVariantId($this->getEnvironmentVariableValueGivenName('VARIANT_ID'));
        $listItems->setQuantity(1);
        $itemsArray = new ArrayOfOneListItem();
        $itemsArray->setOneListItem($listItems);
        $oneListRequest = new OneList();
        $oneListRequest->setItems($itemsArray);
        $oneListRequest->setStoreId($this->getEnvironmentVariableValueGivenName('STORE_ID'));
        $oneListRequest->setListType(ListType::BASKET);
        $param    = [
            'oneList' => $oneListRequest,
            'calculate' => true
        ];
        $response = $this->executeMethod("OneListSave", $param);
        $oneList = $response ? $response->getResult() : null;
        $this->assertInstanceOf(OneList::class, $oneList);
        $this->assertTrue(property_exists($oneList, 'Id'));
        $this->assertTrue(property_exists($oneList, 'ListType'));
        $this->assertTrue(property_exists($oneList, 'CreateDate'));
        $this->assertTrue(property_exists($oneList, 'StoreId'));
        $this->assertTrue(property_exists($oneList, 'TotalAmount'));
        $this->assertTrue(property_exists($oneList, 'TotalDiscAmount'));
        $this->assertTrue(property_exists($oneList, 'TotalNetAmount'));
        $this->assertTrue(property_exists($oneList, 'TotalTaxAmount'));
    }

    /**
     * Save Wish type one list
     * @throws InvalidEnumException
     */
    public function testOneListSaveWish()
    {
        $listItems = new OneListItem();
        $listItems->setItemId($this->getEnvironmentVariableValueGivenName('ITEM_ID'));
        $listItems->setVariantId($this->getEnvironmentVariableValueGivenName('VARIANT_ID'));
        $listItems->setQuantity(1);
        $itemsArray = new ArrayOfOneListItem();
        $itemsArray->setOneListItem($listItems);
        $oneListRequest = new OneList();
        $oneListRequest->setItems($itemsArray);
        $oneListRequest->setCardId($this->getEnvironmentVariableValueGivenName('CARD_ID'));
        $oneListRequest->setStoreId($this->getEnvironmentVariableValueGivenName('STORE_ID'));
        $oneListRequest->setListType(ListType::WISH);
        $param    = [
            'oneList' => $oneListRequest,
            'calculate' => false
        ];
        $response = $this->executeMethod("OneListSave", $param);
        $oneList = $response ? $response->getResult() : null;
        $this->assertInstanceOf(OneList::class, $oneList);
        $this->assertEquals($this->getEnvironmentVariableValueGivenName('CARD_ID'), $oneList->getCardId());
        $this->assertTrue(property_exists($oneList, 'Id'));
        $this->assertTrue(property_exists($oneList, 'ListType'));
        $this->assertTrue(property_exists($oneList, 'CreateDate'));
        $this->assertTrue(property_exists($oneList, 'StoreId'));
        $this->assertTrue(property_exists($oneList, 'TotalAmount'));
        $this->assertTrue(property_exists($oneList, 'TotalDiscAmount'));
        $this->assertTrue(property_exists($oneList, 'TotalNetAmount'));
        $this->assertTrue(property_exists($oneList, 'TotalTaxAmount'));
    }

    /**
     * Get Basket type one lists by Member Card Id
     * @depends testOneListSaveBasket
     */
    public function testOneListGetByCardIdBasket()
    {
        $param    = [
            'cardId' => $this->getEnvironmentVariableValueGivenName('CARD_ID'),
            'listType' => ListType::BASKET,
            'includeLines' => true
        ];
        $response = $this->executeMethod("OneListGetByCardId", $param);
        $result = $response ? $response->getResult() : null;
        $this->assertInstanceOf(ArrayOfOneList::class, $result);
        foreach ($result as $oneList) {
            $this->assertEquals($this->getEnvironmentVariableValueGivenName('CARD_ID'), $oneList->getCardId());
            $this->assertTrue(property_exists($oneList, 'Id'));
            $this->assertTrue(property_exists($oneList, 'CreateDate'));
            $this->assertTrue(property_exists($oneList, 'StoreId'));
            $this->assertTrue(property_exists($oneList, 'TotalAmount'));
            $this->assertTrue(property_exists($oneList, 'TotalDiscAmount'));
            $this->assertTrue(property_exists($oneList, 'TotalNetAmount'));
            $this->assertTrue(property_exists($oneList, 'TotalTaxAmount'));
        }
    }

    /**
     * Get Wish type one lists by Member Card Id
     * @depends testOneListSaveWish
     */
    public function testOneListGetByCardIdWish()
    {
        $param    = [
            'cardId' => $this->getEnvironmentVariableValueGivenName('CARD_ID'),
            'listType' => ListType::WISH,
            'includeLines' => true
        ];
        $response = $this->executeMethod("OneListGetByCardId", $param);
        $result = $response ? $response->getResult() : null;
        $this->assertInstanceOf(ArrayOfOneList::class, $result);
        foreach ($result as $oneList) {
            $this->assertEquals($this->getEnvironmentVariableValueGivenName('CARD_ID'), $oneList->getCardId());
            $this->assertTrue(property_exists($oneList, 'Id'));
            $this->assertTrue(property_exists($oneList, 'CreateDate'));
            $this->assertTrue(property_exists($oneList, 'StoreId'));
            $this->assertTrue(property_exists($oneList, 'TotalAmount'));
            $this->assertTrue(property_exists($oneList, 'TotalDiscAmount'));
            $this->assertTrue(property_exists($oneList, 'TotalNetAmount'));
            $this->assertTrue(property_exists($oneList, 'TotalTaxAmount'));
        }
    }

    /**
     * Delete Basket List By OneList Id
     * @depends testOneListGetByCardIdBasket
     */
    public function testOneListDeleteByIdBasket()
    {
        $param    = [
            'cardId' => $this->getEnvironmentVariableValueGivenName('CARD_ID'),
            'listType' => ListType::BASKET,
            'includeLines' => false
        ];
        $response = $this->executeMethod("OneListGetByCardId", $param);
        $result = $response ? $response->getResult() : null;
        foreach ($result as $oneList) {
            $this->assertEquals($this->getEnvironmentVariableValueGivenName('CARD_ID'), $oneList->getCardId());
            $this->assertTrue(property_exists($oneList, 'Id'));
            $paramDelete = [
                'oneListId' => $oneList->getId()
            ];
            $response    = $this->client->OneListDeleteById($paramDelete);
            $result      = $response->getResult();
            $this->assertTrue(is_bool($result));
        }
    }

    /**
     * Delete wish List By OneList Id
     * @depends testOneListGetByCardIdWish
     */
    public function testOneListDeleteByIdWish()
    {
        $param    = [
            'cardId' => $this->getEnvironmentVariableValueGivenName('CARD_ID'),
            'listType' => ListType::WISH,
            'includeLines' => false
        ];
        $response = $this->executeMethod("OneListGetByCardId", $param);
        $result = $response ? $response->getResult() : null;
        foreach ($result as $oneList) {
            $this->assertEquals($this->getEnvironmentVariableValueGivenName('CARD_ID'), $oneList->getCardId());
            $this->assertTrue(property_exists($oneList, 'Id'));
            $paramDelete = [
                'oneListId' => $oneList->getId()
            ];
            $response    = $this->client->OneListDeleteById($paramDelete);
            $result      = $response->getResult();
            $this->assertTrue(is_bool($result));
        }
    }

    /**
     * Create Customer Order for ClickAndCollect using Cash Order Payment Line only
     * Type - ClickAndCollect
     * User - Member
     * PaymentLine - Cash
     * @depends testOneListSaveBasket
     */
    public function testOrderCreate()
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
        $result->setCollectLocation($this->getEnvironmentVariableValueGivenName('COLLECT_LOCATION'));
        $result->setId('test' . substr(preg_replace("/[^A-Za-z0-9 ]/", '', $result->getId()), 0, 10));
        // Order creation request
        $paramOrderCreate  = [
            'request' => $result
        ];
        $response = $this->executeMethod("OrderCreate", $paramOrderCreate);
        $resultOrderCreate = $response ? $response->getResult() : null;
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
        $this->assertTrue($resultOrderCreate->getClickAndCollectOrder());
    }

    /**
     * Create Customer Order for ClickAndCollect using Online Payment Line only
     * Type - ClickAndCollect
     * User - Member
     * PaymentLine - Online Card
     * @depends testOneListSaveBasket
     */
    public function testOrderCreateOnlinePayment()
    {
        $response       = $this->getOneList($this->getEnvironmentVariableValueGivenName('CARD_ID'));
        $oneListRequest = $response->getResult();
        // Basket calculation
        $entity = new OneListCalculate();
        $entity->setOneList($oneListRequest);
        $response = $this->client->OneListCalculate($entity);
        $result   = $response->getResult();
        $this->assertInstanceOf(Order::class, $result);
        $orderPayment = new OrderPayment();
        $orderPayment->setCurrencyFactor(1)
            ->setAmount($result->getTotalAmount())
            ->setLineNumber('1')
            ->setExternalReference('TEST0012345')
            ->setTenderType($this->getEnvironmentVariableValueGivenName('CREDIT_CARD_TENDER_TYPE'))
            ->setCardType('VISA')
            ->setCardNumber('4111111111111111')
            ->setTokenNumber('1276349812634981234')
            ->setPaymentType('Payment');
        $orderPayments = new ArrayOfOrderPayment();
        $orderPayments->setOrderPayment([$orderPayment]);
        $result->setOrderPayments($orderPayments);
        $result->setOrderType(OrderType::CLICK_AND_COLLECT);
        $result->setCollectLocation($this->getEnvironmentVariableValueGivenName('COLLECT_LOCATION'));
        $result->setId('test1' . substr(preg_replace("/[^A-Za-z0-9 ]/", '', $result->getId()), 0, 10));
        // Order creation request
        $paramOrderCreate  = [
            'request' => $result
        ];
        $response = $this->executeMethod("OrderCreate", $paramOrderCreate);
        $resultOrderCreate = $response ? $response->getResult() : null;
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
        $this->assertTrue($resultOrderCreate->getClickAndCollectOrder());
    }

    /**
     * Create Customer Order for ClickAndCollect using Cash Order Payment Line only
     * Type - ClickAndCollect
     * User - Guest
     * PaymentLine - Cash
     * @depends testOneListSaveBasket
     */
    public function testOrderCreateGuest()
    {
        $response       = $this->getOneList();
        $oneListRequest = $response->getResult();
        // Basket calculation
        $entity = new OneListCalculate();
        $entity->setOneList($oneListRequest);
        $response = $this->client->OneListCalculate($entity);
        $result   = $response->getResult();
        $this->assertInstanceOf(Order::class, $result);
        $result
            ->setEmail($this->getEnvironmentVariableValueGivenName('EMAIL'))
            ->setShipToEmail($this->getEnvironmentVariableValueGivenName('EMAIL'))
            ->setContactName('test')
            ->setShipToName('test');
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
        $result->setCollectLocation($this->getEnvironmentVariableValueGivenName('COLLECT_LOCATION'));
        $result->setId('test' . substr(preg_replace("/[^A-Za-z0-9 ]/", '', $result->getId()), 0, 10));
        // Order creation request
        $paramOrderCreate  = [
            'request' => $result
        ];
        $response = $this->executeMethod("OrderCreate", $paramOrderCreate);
        $resultOrderCreate = $response ? $response->getResult() : null;
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
        $this->assertTrue($resultOrderCreate->getClickAndCollectOrder());
    }

    /**
     * Create Customer Order for ClickAndCollect using Cash, Gift Card and Loyalty Payment Line
     * Type - ClickAndCollect
     * User - Member
     * PaymentLines - Cash + Gift Card + Loyalty
     * @depends testOneListSaveBasket
     */
    public function testOrderCreateWithGiftCardAndLoyalty()
    {
        $response       = $this->getOneList($this->getEnvironmentVariableValueGivenName('CARD_ID'));
        $oneListRequest = $response->getResult();
        $this->assertInstanceOf(OneList::class, $oneListRequest);
        // Basket calculation
        $entity = new OneListCalculate();
        $entity->setOneList($oneListRequest);
        $response = $this->client->OneListCalculate($entity);
        $result   = $response->getResult();
        $this->assertInstanceOf(Order::class, $result);
        $orderPaymentArray = [];
        $preApprovedDate   = date('Y-m-d', strtotime('+1 years'));
        $orderPayment      = new OrderPayment();
        $orderPayment->setCurrencyFactor(1)
            ->setAmount($result->getTotalAmount() - 0.1 - 1)
            ->setLineNumber('1')
            ->setExternalReference('TEST0012345')
            ->setTenderType($this->getEnvironmentVariableValueGivenName('CASH_TENDER_TYPE'));
        $orderPaymentArray[] = $orderPayment;
        $orderPaymentLoyalty = new OrderPayment();
        $orderPaymentLoyalty->setCurrencyCode('LOY')
            ->setPaymentType(PaymentType::PAYMENT)
            ->setCurrencyFactor('0.10000000000000000000')
            ->setLineNumber('2')
            ->setCardNumber($this->getEnvironmentVariableValueGivenName('CARD_ID'))
            ->setExternalReference('TEST0012345')
            ->setAmount('1')
            ->setPreApprovedValidDate($preApprovedDate)
            ->setTenderType($this->getEnvironmentVariableValueGivenName('LOYALTY_POINTS_TENDER_TYPE'));
        $orderPaymentArray[] = $orderPaymentLoyalty;
        $orderPaymentGift    = new OrderPayment();
        $orderPaymentGift->setCurrencyFactor(1)
            ->setAmount('1')
            ->setLineNumber('3')
            ->setCardNumber($this->getEnvironmentVariableValueGivenName('GIFTCARDCODE'))
            ->setExternalReference('TEST0012345')
            ->setPreApprovedValidDate($preApprovedDate)
            ->setTenderType($this->getEnvironmentVariableValueGivenName('GIFT_CARD_TENDER_TYPE'));
        $orderPaymentArray[] = $orderPaymentGift;
        $orderPayments       = new ArrayOfOrderPayment();
        $orderPayments->setOrderPayment($orderPaymentArray);
        $result->setOrderPayments($orderPayments);
        $result->setOrderType(OrderType::CLICK_AND_COLLECT);
        $result->setCollectLocation($this->getEnvironmentVariableValueGivenName('COLLECT_LOCATION'));
        $result->setPointBalance('8668');
        $result->setId('test' . substr(preg_replace("/[^A-Za-z0-9 ]/", '', $result->getId()), 0, 10));
        // Order creation request
        $paramOrderCreate = [
            'request' => $result
        ];
        $response = $this->executeMethod("OrderCreate", $paramOrderCreate);
        $resultOrderCreate = $response ? $response->getResult() : null;
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
        $this->assertTrue($resultOrderCreate->getClickAndCollectOrder());
    }

    /**
     * Create Customer Order for Sale using Online Payment Line only
     * Type - Sale
     * User - Guest
     * PaymentLine - Card
     * @depends testOneListSaveBasket
     */
    public function testOrderCreateOnlinePaymentSaleGuest()
    {
        $response       = $this->getOneList();
        $oneListRequest = $response->getResult();
        // Basket calculation
        $entity = new OneListCalculate();
        $entity->setOneList($oneListRequest);
        $response = $this->client->OneListCalculate($entity);
        $result   = $response->getResult();
        $this->assertInstanceOf(Order::class, $result);
        $result->setOrderType(OrderType::SALE);
        $omniAddress1 = new Address();
        $omniAddress1->setCity('KL')
            ->setAddress1('Jalan')
            ->setAddress2('Klang')
            ->setCountry('MY')
            ->setStateProvinceRegion(substr('Kuala Lumpur', 0, 10))
            ->setPostCode('47301');
        $omniAddress2 = new Address();
        $omniAddress2->setCity('KL')
            ->setAddress1('Jalan')
            ->setAddress2('Klang')
            ->setCountry('MY')
            ->setStateProvinceRegion(substr('Kuala Lumpur', 0, 10))
            ->setPostCode('47301');
        $result
            ->setContactId('')
            ->setCardId('')
            ->setEmail($this->getEnvironmentVariableValueGivenName('EMAIL'))
            ->setShipToEmail($this->getEnvironmentVariableValueGivenName('EMAIL'))
            ->setContactName('Testing')
            ->setShipToName('Testing')
            ->setContactAddress($omniAddress1)
            ->setShipToAddress($omniAddress2)
            ->setStoreId($this->getEnvironmentVariableValueGivenName('STORE_ID'));
        $orderLines        = $result->getOrderLines()->getOrderLine();
        $shipmentOrderLine = new OrderLine();
        $shipmentOrderLine->setPrice('5')
            ->setNetPrice('5')
            ->setNetAmount('5')
            ->setAmount('5')
            ->setItemId('66010')
            ->setLineType('Item')
            ->setQuantity(1);
        array_push($orderLines, $shipmentOrderLine);
        $result->setOrderLines($orderLines);
        $orderPayment = new OrderPayment();
        $orderPayment->setCurrencyFactor(1)
            ->setAmount($result->getTotalAmount() + $shipmentOrderLine->getAmount())
            ->setLineNumber('1')
            ->setExternalReference('TEST0012345')
            ->setTenderType($this->getEnvironmentVariableValueGivenName('CREDIT_CARD_TENDER_TYPE'))
            ->setCardType('VISA')
            ->setCardNumber('4111111111111111')
            ->setTokenNumber('1276349812634981234')
            ->setPaymentType('Payment');
        $orderPayments = new ArrayOfOrderPayment();
        $orderPayments->setOrderPayment([$orderPayment]);
        $result->setOrderPayments($orderPayments);
        $result->setId('test' . substr(preg_replace("/[^A-Za-z0-9 ]/", '', $result->getId()), 0, 10));
        // Order creation request
        $paramOrderCreate  = [
            'request' => $result
        ];
        $response = $this->executeMethod("OrderCreate", $paramOrderCreate);
        $resultOrderCreate = $response ? $response->getResult() : null;
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
