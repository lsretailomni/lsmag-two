<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\ClassMap;
use \Ls\Omni\Client\Ecommerce\Entity\GiftCard;
use \Ls\Omni\Client\Ecommerce\Entity\GiftCardGetBalance;
use \Ls\Omni\Service\ServiceType;
use \Ls\Omni\Service\Soap\Client as OmniClient;
use PHPUnit\Framework\TestCase;
use Zend\Uri\UriFactory;

class GiftCardGetBalanceTest extends TestCase
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
        $entity = new GiftCardGetBalance();
        $entity->setCardNo($_ENV['GIFTCARDCODE']);
        $response = $this->client->GiftCardGetBalance($entity);
        $result   = $response->getResult();
        $this->assertInstanceOf(GiftCard::class, $result);
        $this->assertNotNull($result->getBalance());
        $this->assertNotNull($result->getId());
    }
}