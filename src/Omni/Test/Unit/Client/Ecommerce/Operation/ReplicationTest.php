<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use Ls\Omni\Client\Ecommerce\ClassMap;
use Ls\Omni\Service\ServiceType;
use Ls\Omni\Service\Soap\Client as OmniClient;
use Zend\Uri\UriFactory;

class ReplicationTest extends \PHPUnit\Framework\TestCase
{
    /** @var OmniClient */
    public $client;

    /** @var array */
    public $params;

    protected function setUp()
    {
        $baseUrl = $_ENV['BASE_URL'];
        $url = implode('/', [$baseUrl, 'UCService.svc?singlewsdl']);
        $service_type = new ServiceType(ServiceType::ECOMMERCE);
        $uri = UriFactory::factory($url);
        $this->client = new OmniClient($uri, $service_type);
        $this->client->setClassmap(ClassMap::getClassMap());
        $this->params = array(
            'BatchSize' => '1000',
            'FullReplication' => '1',
            'LastKey' => '0',
            'MaxKey' => '0',
            'StoreId' => 'S0013',
            'TerminalId' => '0'
        );
    }

    public function testClient()
    {
        $this->assertNotNull($this->client);
    }
}