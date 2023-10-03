<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfOneList;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfOneListItem;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfOneListPublishedOffer;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfOrderLine;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\ListType;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\OrderType;
use \Ls\Omni\Client\Ecommerce\Entity\LoyItem;
use \Ls\Omni\Client\Ecommerce\Entity\OneList;
use \Ls\Omni\Client\Ecommerce\Entity\OneListCalculate;
use \Ls\Omni\Client\Ecommerce\Entity\OneListItem;
use \Ls\Omni\Client\Ecommerce\Entity\OneListPublishedOffer;
use \Ls\Omni\Client\Ecommerce\Entity\Order;
use \Ls\Omni\Exception\InvalidEnumException;

/**
 * It will cover all the methods for Add to cart - Basket Calculation
 *
 */
class AddToCartMethodsTest extends OmniClientSetupTest
{
    /**
     * Lookup Item
     */
    public function testItemGetbyId()
    {
        $param    = [
            'itemId'  => $this->getEnvironmentVariableValueGivenName('ITEM_ID'),
            'storeId' => $this->getEnvironmentVariableValueGivenName('STORE_ID'),
        ];
        $response = $this->client->ItemGetbyId($param);
        $result   = $response->getResult();
        $this->assertInstanceOf(LoyItem::class, $result);
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
        $response = $this->client->OneListCalculate($entity);
        $result   = $response->getResult();
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
     * Calculates OneList Basket Calculation for guest and returns Order Object
     * @throws InvalidEnumException
     */
    public function testOneListCalculateGuest()
    {
        $oneListRequest = new OneList();
        $listItems      = new OneListItem();
        $listItems->setItemId($this->getEnvironmentVariableValueGivenName('SIMPLE_ITEM_ID'));
        $listItems->setQuantity(1);
        $itemsArray = new ArrayOfOneListItem();
        $itemsArray->setOneListItem($listItems);
        $oneListRequest->setItems($itemsArray);
        $oneListRequest->setStoreId($this->getEnvironmentVariableValueGivenName('STORE_ID'));
        $oneListRequest->setListType(ListType::BASKET);
        $entity = new OneListCalculate();
        $entity->setOneList($oneListRequest);
        $response = $this->client->OneListCalculate($entity);
        $result   = $response->getResult();
        $this->assertInstanceOf(Order::class, $result);
        $this->assertEquals($this->getEnvironmentVariableValueGivenName('STORE_ID'), $result->getStoreId());
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
            'oneList'   => $oneListRequest,
            'calculate' => true
        ];
        $response = $this->client->OneListSave($param);
        $oneList  = $response->getResult();
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
            'cardId'       => $this->getEnvironmentVariableValueGivenName('CARD_ID'),
            'listType'     => ListType::BASKET,
            'includeLines' => true
        ];
        $response = $this->client->OneListGetByCardId($param);
        $result   = $response->getResult();
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
     * Apply Coupon as Published Offer with Card Id
     * @throws InvalidEnumException
     */
    public function testApplyCoupon()
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
        $offer  = new OneListPublishedOffer();
        $offers = new ArrayOfOneListPublishedOffer();
        $offers->setOneListPublishedOffer($offer);
        $offer->setId($this->getEnvironmentVariableValueGivenName('COUPON_CODE'));
        $offer->setType('Coupon');
        $oneListRequest->setPublishedOffers($offers);
        $param    = [
            'oneList'   => $oneListRequest,
            'calculate' => true
        ];
        $response = $this->client->OneListSave($param);
        $oneList  = $response->getResult();
        $this->assertInstanceOf(OneList::class, $oneList);
        $this->assertEquals($this->getEnvironmentVariableValueGivenName('CARD_ID'), $oneList->getCardId());
        $this->assertTrue(property_exists($oneList, 'Id'));
        $this->assertTrue(property_exists($oneList, 'ListType'));
        $this->assertTrue(property_exists($oneList, 'PublishedOffers'));
        $this->assertTrue(property_exists($oneList, 'CreateDate'));
        $this->assertTrue(property_exists($oneList, 'StoreId'));
        $this->assertTrue(property_exists($oneList, 'TotalAmount'));
        $this->assertTrue(property_exists($oneList, 'TotalDiscAmount'));
        $this->assertTrue(property_exists($oneList, 'TotalNetAmount'));
        $this->assertTrue(property_exists($oneList, 'TotalTaxAmount'));
    }
}
