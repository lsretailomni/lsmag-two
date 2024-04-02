<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\ClassMap;
use \Ls\Omni\Service\ServiceType;
use \Ls\Omni\Service\Soap\Client as OmniClient;
use PHPUnit\Framework\TestCase;
use Laminas\Uri\UriFactory;

class OmniClientSetupTest extends TestCase
{
    /** @var OmniClient */
    public $client;

    protected function setUp(): void
    {
        $baseUrl      = $this->getEnvironmentVariableValueGivenName('BASE_URL');
        $url          = implode('/', [$baseUrl, 'UCService.svc?singlewsdl']);
        $service_type = new ServiceType(ServiceType::ECOMMERCE);
        $uri          = UriFactory::factory($url);
        $this->client = new OmniClient($uri, $service_type);
        $this->client->setClassmap(ClassMap::getClassMap());
        $this->assertNotNull($this->client);
    }

    public function testExecute()
    {
        $pong = $this->client->Ping();
        $this->assertThat(
            $pong->getResult(),
            $this->logicalOr(
                $this->stringContains('PONG OK> Successfully connected to [Commerce Service for LS Central DB] & [LS Central DB] & [LS Central WS]'),
                $this->stringContains('PONG OK> Successfully connected to [Commerce Service for LS Central DB] & [LS SaaS] & [LS Central WS]')
            )
        );
    }

    /**
     * Get environment variable value given name
     *
     * @param $name
     * @return array|false|string
     */
    public function getEnvironmentVariableValueGivenName($name)
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        return getenv($name);
    }

    /**
     * Execute given method
     *
     * @param $methodName
     * @param $param
     * @return null
     */
    public function executeMethod($methodName, $param = null)
    {
        try {

            if ($param) {
                $response = $this->client->{$methodName}($param);
            } else {
                $response = $this->client->{$methodName}();
            }

        } catch (\Exception $e) {
            // phpcs:ignore Magento2.Security.LanguageConstruct.DirectOutput
            echo $e->getMessage();
            $response = null;
        }

        return $response;
    }
}
