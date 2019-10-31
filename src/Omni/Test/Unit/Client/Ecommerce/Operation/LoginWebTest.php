<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\ClassMap;
use \Ls\Omni\Client\Ecommerce\Entity\LoginWeb;
use \Ls\Omni\Client\Ecommerce\Entity\LoginWebResponse;
use \Ls\Omni\Service\ServiceType;
use \Ls\Omni\Service\Soap\Client as OmniClient;
use Zend\Uri\UriFactory;

class LoginWebTest extends \PHPUnit\Framework\TestCase
{
    /** @var OmniClient */
    public $client;

    public $username;

    public $email;

    public $password;

    protected function setUp()
    {
        $baseUrl = $_ENV['BASE_URL'];
        $this->username = $_ENV['USERNAME'];
        $this->email = $_ENV['EMAIL'];
        $this->password = $_ENV['PASSWORD'];
        $url = implode('/', [$baseUrl, 'UCService.svc?singlewsdl']);
        $service_type = new ServiceType(ServiceType::ECOMMERCE);
        $uri = UriFactory::factory($url);
        $this->client = new OmniClient($uri, $service_type);
        $this->client->setClassmap(ClassMap::getClassMap());
    }

    public function testLoginUserName()
    {
        $this->assertNotNull($this->client);
        $params = array(
            'userName' => $this->username,
            'password' => $this->password
        );
        $response = $this->client->LoginWeb($params);
        $this->assertInstanceOf(LoginWebResponse::class, $response);
    }
}