<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfOneList;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfOneListItem;
use Ls\Omni\Client\Ecommerce\Entity\ArrayOfOneListPublishedOffer;
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
 * Class AddToCartMethodsTest
 * @package Ls\Omni\Test\Unit\Client\Ecommerce\Operation
 */
class AddToCartMethodsTest extends OmniClientSetupTest
{
    /**
     * Lookup Item
     */
    public function testItemGetbyId()
    {
        $param    = [
            'itemId'  => $_ENV['ITEM_ID'],
            'storeId' => $_ENV['STORE_ID'],
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
        $listItems->setItemId($_ENV['ITEM_ID']);
        $listItems->setVariantId($_ENV['VARIANT_ID']);
        $listItems->setQuantity(1);
        $itemsArray = new ArrayOfOneListItem();
        $itemsArray->setOneListItem($listItems);
        $oneListRequest->setItems($itemsArray);
        $oneListRequest->setCardId($_ENV['CARD_ID']);
        $oneListRequest->setStoreId($_ENV['STORE_ID']);
        $oneListRequest->setListType(ListType::BASKET);
        $entity = new OneListCalculate();
        $entity->setOneList($oneListRequest);
        $response = $this->client->OneListCalculate($entity);
        $result   = $response->getResult();
        $this->assertInstanceOf(Order::class, $result);
        $this->assertEquals($_ENV['STORE_ID'], $result->getStoreId());
        $this->assertEquals($_ENV['CARD_ID'], $result->getCardId());
        $this->assertNotNull($result->getTotalAmount());
        $this->assertNotNull($result->getTotalNetAmount());
        $this->assertInternalType('boolean', $result->getPosted());
        $this->assertInternalType('string', $result->getOrderType());
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
        $listItems->setItemId($_ENV['SIMPLE_ITEM_ID']);
        $listItems->setQuantity(1);
        $itemsArray = new ArrayOfOneListItem();
        $itemsArray->setOneListItem($listItems);
        $oneListRequest->setItems($itemsArray);
        $oneListRequest->setStoreId($_ENV['STORE_ID']);
        $oneListRequest->setListType(ListType::BASKET);
        $entity = new OneListCalculate();
        $entity->setOneList($oneListRequest);
        $response = $this->client->OneListCalculate($entity);
        $result   = $response->getResult();
        $this->assertInstanceOf(Order::class, $result);
        $this->assertEquals($_ENV['STORE_ID'], $result->getStoreId());
        $this->assertNotNull($result->getTotalAmount());
        $this->assertNotNull($result->getTotalNetAmount());
        $this->assertInternalType('boolean', $result->getPosted());
        $this->assertInternalType('string', $result->getOrderType());
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
        $listItems->setItemId($_ENV['ITEM_ID']);
        $listItems->setVariantId($_ENV['VARIANT_ID']);
        $listItems->setQuantity(1);
        $itemsArray = new ArrayOfOneListItem();
        $itemsArray->setOneListItem($listItems);
        $oneListRequest = new OneList();
        $oneListRequest->setItems($itemsArray);
        $oneListRequest->setCardId($_ENV['CARD_ID']);
        $oneListRequest->setStoreId($_ENV['STORE_ID']);
        $oneListRequest->setListType(ListType::BASKET);
        $param    = [
            'oneList'   => $oneListRequest,
            'calculate' => true
        ];
        $response = $this->client->OneListSave($param);
        $oneList  = $response->getResult();
        $this->assertInstanceOf(OneList::class, $oneList);
        $this->assertEquals($_ENV['CARD_ID'], $oneList->getCardId());
        $this->assertObjectHasAttribute('Id', $oneList);
        $this->assertObjectHasAttribute('ListType', $oneList);
        $this->assertObjectHasAttribute('CreateDate', $oneList);
        $this->assertObjectHasAttribute('StoreId', $oneList);
        $this->assertObjectHasAttribute('TotalAmount', $oneList);
        $this->assertObjectHasAttribute('TotalDiscAmount', $oneList);
        $this->assertObjectHasAttribute('TotalNetAmount', $oneList);
        $this->assertObjectHasAttribute('TotalTaxAmount', $oneList);
    }

    /**
     * Get Basket type one lists by Member Card Id
     * @depends testOneListSaveBasket
     */
    public function testOneListGetByCardIdBasket()
    {
        $param    = [
            'cardId'       => $_ENV['CARD_ID'],
            'listType'     => ListType::BASKET,
            'includeLines' => true
        ];
        $response = $this->client->OneListGetByCardId($param);
        $result   = $response->getResult();
        $this->assertInstanceOf(ArrayOfOneList::class, $result);
        foreach ($result as $oneList) {
            $this->assertEquals($_ENV['CARD_ID'], $oneList->getCardId());
            $this->assertObjectHasAttribute('Id', $oneList);
            $this->assertObjectHasAttribute('CreateDate', $oneList);
            $this->assertObjectHasAttribute('StoreId', $oneList);
            $this->assertObjectHasAttribute('TotalAmount', $oneList);
            $this->assertObjectHasAttribute('TotalDiscAmount', $oneList);
            $this->assertObjectHasAttribute('TotalNetAmount', $oneList);
            $this->assertObjectHasAttribute('TotalTaxAmount', $oneList);
        }
    }

    /**
     * Apply Coupon as Published Offer with Card Id
     * @throws InvalidEnumException
     */
    public function testApplyCoupon()
    {
        $listItems = new OneListItem();
        $listItems->setItemId($_ENV['ITEM_ID']);
        $listItems->setVariantId($_ENV['VARIANT_ID']);
        $listItems->setQuantity(1);
        $itemsArray = new ArrayOfOneListItem();
        $itemsArray->setOneListItem($listItems);
        $oneListRequest = new OneList();
        $oneListRequest->setItems($itemsArray);
        $oneListRequest->setCardId($_ENV['CARD_ID']);
        $oneListRequest->setStoreId($_ENV['STORE_ID']);
        $oneListRequest->setListType(ListType::BASKET);
        $offer  = new OneListPublishedOffer();
        $offers = new ArrayOfOneListPublishedOffer();
        $offers->setOneListPublishedOffer($offer);
        $offer->setId($_ENV['COUPON_CODE']);
        $offer->setType('Coupon');
        $oneListRequest->setPublishedOffers($offers);
        $param    = [
            'oneList'   => $oneListRequest,
            'calculate' => true
        ];
        $response = $this->client->OneListSave($param);
        $oneList  = $response->getResult();
        $this->assertInstanceOf(OneList::class, $oneList);
        $this->assertEquals($_ENV['CARD_ID'], $oneList->getCardId());
        $this->assertObjectHasAttribute('Id', $oneList);
        $this->assertObjectHasAttribute('ListType', $oneList);
        $this->assertObjectHasAttribute('PublishedOffers', $oneList);
        $this->assertObjectHasAttribute('CreateDate', $oneList);
        $this->assertObjectHasAttribute('StoreId', $oneList);
        $this->assertObjectHasAttribute('TotalAmount', $oneList);
        $this->assertObjectHasAttribute('TotalDiscAmount', $oneList);
        $this->assertObjectHasAttribute('TotalNetAmount', $oneList);
        $this->assertObjectHasAttribute('TotalTaxAmount', $oneList);
    }

    /**
     * Apply Coupon as Published Offer for Guest
     * @throws InvalidEnumException
     */
    public function testApplyCouponGuest()
    {
        $listItems = new OneListItem();
        $listItems->setItemId($_ENV['ITEM_ID']);
        $listItems->setVariantId($_ENV['VARIANT_ID']);
        $listItems->setQuantity(1);
        $itemsArray = new ArrayOfOneListItem();
        $itemsArray->setOneListItem($listItems);
        $oneListRequest = new OneList();
        $oneListRequest->setItems($itemsArray);
        $oneListRequest->setStoreId($_ENV['STORE_ID']);
        $oneListRequest->setListType(ListType::BASKET);
        $offer  = new OneListPublishedOffer();
        $offers = new ArrayOfOneListPublishedOffer();
        $offers->setOneListPublishedOffer($offer);
        $offer->setId($_ENV['COUPON_CODE']);
        $offer->setType('Coupon');
        $oneListRequest->setPublishedOffers($offers);
        $param    = [
            'oneList'   => $oneListRequest,
            'calculate' => true
        ];
        $response = $this->client->OneListSave($param);
        $oneList  = $response->getResult();
        $this->assertInstanceOf(OneList::class, $oneList);
        $this->assertObjectHasAttribute('Id', $oneList);
        $this->assertObjectHasAttribute('ListType', $oneList);
        $this->assertObjectHasAttribute('PublishedOffers', $oneList);
        $this->assertObjectHasAttribute('CreateDate', $oneList);
        $this->assertObjectHasAttribute('StoreId', $oneList);
        $this->assertObjectHasAttribute('TotalAmount', $oneList);
        $this->assertObjectHasAttribute('TotalDiscAmount', $oneList);
        $this->assertObjectHasAttribute('TotalNetAmount', $oneList);
        $this->assertObjectHasAttribute('TotalTaxAmount', $oneList);
    }
}
