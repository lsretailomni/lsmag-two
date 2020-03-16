<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\ClassMap;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Entity\ContactSearchResponse;
use \Ls\Omni\Service\ServiceType;
use \Ls\Omni\Service\Soap\Client as OmniClient;
use PHPUnit\Framework\TestCase;
use Zend\Uri\UriFactory;

class ContactSearchTest extends TestCase
{
    /** @var OmniClient */
    public $client;

    public $username;

    public $email;

    protected function setUp()
    {
        $baseUrl        = $_ENV['BASE_URL'];
        $this->username = $_ENV['USERNAME'];
        $this->email    = $_ENV['EMAIL'];
        $url            = implode('/', [$baseUrl, 'UCService.svc?singlewsdl']);
        $service_type   = new ServiceType(ServiceType::ECOMMERCE);
        $uri            = UriFactory::factory($url);
        $this->client   = new OmniClient($uri, $service_type);
        $this->client->setClassmap(ClassMap::getClassMap());
    }

    public function testSearchUsername()
    {
        $this->assertNotNull($this->client);
        $params   = [
            'searchType' => Entity\Enum\ContactSearchType::USER_NAME,
            'search'     => $this->username
        ];
        $response = $this->client->ContactSearch($params);
        $this->assertInstanceOf(ContactSearchResponse::class, $response);
    }

    public function testSearchEmail()
    {
        $this->assertNotNull($this->client);
        $params   = [
            'searchType' => Entity\Enum\ContactSearchType::EMAIL,
            'search'     => $this->email
        ];
        $response = $this->client->ContactSearch($params);
        $this->assertInstanceOf(ContactSearchResponse::class, $response);
    }
}