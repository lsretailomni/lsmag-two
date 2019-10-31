<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\ClassMap;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfMemberContact;
use \Ls\Omni\Client\Ecommerce\Entity\ContactSearch;
use \Ls\Omni\Client\Ecommerce\Entity\ContactSearchResponse;
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
        $this->assertNotNull($this->client);
    }

    public function testSearchUsername()
    {
        try {
            $params = array(
                'searchType' => Entity\Enum\ContactSearchType::USER_NAME,
                'search' => $this->username
            );
            $response = $this->client->ContactSearch($params);
            $this->assertInstanceOf(ContactSearchResponse::class, $response);
            $this->assertInstanceOf(ArrayOfMemberContact::class, $response->getResult());
            $this->assertGreaterThanOrEqual(1, count($response->getResult()->getMemberContact()));
        } catch (\SoapFault $e) {
            echo $e->getMessage();
        }
    }

    /**
     * @depends testSearchUsername
     */
    public function testLoginUserName()
    {
        try {
            $params = array(
                'userName' => $this->username,
                'password' => $this->password
            );
            $response = $this->client->LoginWeb($params);
            $this->assertInstanceOf(LoginWebResponse::class, $response);
        } catch (\SoapFault $e) {
            echo $e->getMessage();
        }
    }
}