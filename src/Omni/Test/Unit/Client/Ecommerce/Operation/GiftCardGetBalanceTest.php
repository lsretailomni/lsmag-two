<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\Entity\GiftCard;
use \Ls\Omni\Client\Ecommerce\Entity\GiftCardGetBalance;

class GiftCardGetBalanceTest extends OmniClientSetupTest
{
    public function testExecute()
    {
        $this->assertNotNull($this->client);
        $entity = new GiftCardGetBalance();
        $entity->setCardNo($this->getEnvironmentVariableValueGivenName('GIFTCARDCODE'));
        $response = $this->client->GiftCardGetBalance($entity);
        $result   = $response->getResult();
        $this->assertInstanceOf(GiftCard::class, $result);
        $this->assertNotNull($result->getBalance());
        $this->assertNotNull($result->getId());
    }
}
