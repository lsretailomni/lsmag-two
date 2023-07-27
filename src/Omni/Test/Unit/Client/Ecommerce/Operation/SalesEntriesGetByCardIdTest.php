<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\ClassMap;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfSalesEntry;
use \Ls\Omni\Service\ServiceType;
use \Ls\Omni\Service\Soap\Client as OmniClient;
use PHPUnit\Framework\TestCase;
use Laminas\Uri\UriFactory;

class SalesEntriesGetByCardIdTest extends TestCase
{
    protected function setUp(): void
    {
        $baseUrl      = getenv('BASE_URL');
        $url          = implode('/', [$baseUrl, 'UCService.svc?singlewsdl']);
        $service_type = new ServiceType(ServiceType::ECOMMERCE);
        $uri          = UriFactory::factory($url);
        $this->client = new OmniClient($uri, $service_type);
        $this->client->setClassmap(ClassMap::getClassMap());
    }

    public function testExecute()
    {
        $this->assertNotNull($this->client);
        $param    = [
            'cardId' => $_ENV['CARD_ID']
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
