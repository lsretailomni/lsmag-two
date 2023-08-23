<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfSalesEntry;

class SalesEntriesGetByCardIdTest extends OmniClientSetupTest
{
    public function testExecute()
    {
        $this->assertNotNull($this->client);
        $param    = [
            'cardId' => $this->getEnvironmentVariableValueGivenName('CARD_ID')
        ];
        $response = $this->client->SalesEntriesGetByCardId($param);
        $result   = $response->getResult();
        $this->assertInstanceOf(ArrayOfSalesEntry::class, $result);
        $testOrder = $result->getSalesEntry();
        if (!empty($testOrder)) {
            $this->assertNotNull($testOrder[0]->getCardId());
            $this->assertNotNull($testOrder[0]->getId());
            $this->assertNotNull($testOrder[0]->getStatus());
            $this->assertNotNull($testOrder[0]->getIdType());
            $this->assertNotNull($testOrder[0]->getStoreId());
            $this->assertNotNull($testOrder[0]->getTotalAmount());
        }
    }
}
