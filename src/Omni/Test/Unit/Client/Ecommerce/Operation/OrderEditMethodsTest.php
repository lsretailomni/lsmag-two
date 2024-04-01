<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use Ls\Omni\Client\Ecommerce\Entity\ArrayOfOrderLine;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfOrderPayment;
use Ls\Omni\Client\Ecommerce\Entity\Enum\LineType;
use Ls\Omni\Client\Ecommerce\Entity\Enum\OrderEditType;
use Ls\Omni\Client\Ecommerce\Entity\OrderLine;
use \Ls\Omni\Client\Ecommerce\Entity\OrderPayment;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntry;
use \Ls\Omni\Client\Ecommerce\Entity\Order as CommerceOrder;
use \Ls\Omni\Client\Ecommerce\Entity\Address;

/**
 * It will cover test for order edit
 */
class OrderEditMethodsTest extends OmniClientSetupTest
{
    /**
     * Edit customer order
     * Type - Order Edit
     * User - Member
     * PaymentLine - Cash
     * @depends testOneListSaveBasket
     */
    public function testOrderEdit()
    {
        $orderObject = new CommerceOrder();
        $orderObject->setStoreId($this->getEnvironmentVariableValueGivenName('STORE_ID'));
        $orderObject->setCardId($this->getEnvironmentVariableValueGivenName('CARD_ID'));
        $orderObject->setEmail($this->getEnvironmentVariableValueGivenName('EMAIL'));
        $orderPayment = new OrderPayment();
        $orderPayment->setCurrencyFactor(1)
            ->setAmount(100)
            ->setLineNumber('20')
            ->setExternalReference('TEST0012345')
            ->setTenderType($this->getEnvironmentVariableValueGivenName('CASH_TENDER_TYPE'));
        $orderPayments = new ArrayOfOrderPayment();
        $orderPayments->setOrderPayment([$orderPayment]);
        $orderObject->setOrderPayments($orderPayments);
        $commerceAddress = new Address();
        $commerceAddress->setCity('KL')
            ->setAddress1('Jalan')
            ->setAddress2('Klang')
            ->setCountry('MY')
            ->setStateProvinceRegion('Kuala Lumpur')
            ->setPostCode('47301');
        $orderObject
            ->setId('TEST0012345')
            ->setCardId($this->getEnvironmentVariableValueGivenName('CARD_ID'))
            ->setEmail($this->getEnvironmentVariableValueGivenName('EMAIL'))
            ->setShipToEmail($this->getEnvironmentVariableValueGivenName('EMAIL'))
            ->setContactName('test')
            ->setShipToName('test')
            ->setContactAddress($commerceAddress)
            ->setShipToAddress($commerceAddress)
            ->setStoreId($this->getEnvironmentVariableValueGivenName('STORE_ID'));

        $lineOrder = new OrderLine();
        $lineOrder->setPrice(60)
            ->setAmount(60)
            ->setNetPrice(40)
            ->setNetAmount(40)
            ->setTaxAmount(20)
            ->setItemId($this->getEnvironmentVariableValueGivenName('ITEM_ID'))
            ->setVariantId($this->getEnvironmentVariableValueGivenName('VARIANT_ID'))
            ->setLineType(LineType::ITEM)
            ->setLineNumber(100)
            ->setQuantity(2);

        $orderLinesArray = new ArrayOfOrderLine();
        $orderLinesArray->setOrderLine([$lineOrder]);
        $orderObject->setOrderLines($orderLinesArray);
        // Order edit request
        $paramEditCreate  = [
            'request' => $orderObject,
            'editType' => OrderEditType::GENERAL,
            'orderId' => $this->getEnvironmentVariableValueGivenName('DOCUMENT_ID')
        ];
        $responseOrder     = $this->client->OrderEdit($paramEditCreate);
        $resultOrderEdit = $responseOrder->getResult();
        $this->assertInstanceOf(SalesEntry::class, $resultOrderEdit);
        $this->assertTrue(property_exists($resultOrderEdit, 'Id'));
        $this->assertTrue(property_exists($resultOrderEdit, 'CardId'));
        $this->assertTrue(property_exists($resultOrderEdit, 'ExternalId'));
        $this->assertTrue(property_exists($resultOrderEdit, 'StoreId'));
        $this->assertTrue(property_exists($resultOrderEdit, 'TotalAmount'));
        $this->assertTrue(property_exists($resultOrderEdit, 'TotalDiscount'));
        $this->assertTrue(property_exists($resultOrderEdit, 'TotalNetAmount'));
        $this->assertTrue(property_exists($resultOrderEdit, 'Status'));
        $this->assertTrue(property_exists($resultOrderEdit, 'Payments'));
        $this->assertTrue(property_exists($resultOrderEdit, 'Lines'));
    }
}
