<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\ClassMap;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfStore;
use \Ls\Omni\Service\ServiceType;
use \Ls\Omni\Service\Soap\Client as OmniClient;
use Zend\Uri\UriFactory;

class StoresGetAllTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        $baseUrl      = $_ENV['BASE_URL'];
        $url          = implode('/', [$baseUrl, 'UCService.svc?singlewsdl']);
        $service_type = new ServiceType(ServiceType::ECOMMERCE);
        $uri          = UriFactory::factory($url);
        $this->client = new OmniClient($uri, $service_type);
        $this->client->setClassmap(ClassMap::getClassMap());
    }

    public function testExecute()
    {
        $this->assertNotNull($this->client);
        $response = $this->client->StoresGetAll();
        $result   = $response->getResult();
        $this->assertInstanceOf(ArrayOfStore::class, $result);
        $stores = $result->getStore();
        foreach ($stores as $store) {
            if (!empty($store)) {
                $this->assertNotNull($store->getLatitude());
                $this->assertNotNull($store->getLatitude());
                $this->assertNotNull($store->getStoreHours());
                $this->assertNotNull($store->getAddress());
            }
        }
    }
}
