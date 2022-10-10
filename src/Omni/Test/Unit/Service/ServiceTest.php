<?php

namespace Ls\Omni\Test\Unit\Service;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Operation\Ping;
use \Ls\Omni\Service\Service;
use \Ls\Omni\Service\ServiceType;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class ServiceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Service
     */
    protected $model;

    /**
     * @var \Magento\Framework\App\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $config;

    /**
     * @var LSR|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $lsrMock;

    protected $baseUrl;

    protected function setUp(): void
    {
        $this->baseUrl = $_ENV['BASE_URL'];
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->lsrMock = $this->createMock(LSR::class);
        $this->serviceMock = $this->createMock(Service::class);
        $this->config = $this->createMock(\Magento\Framework\App\Config::class);
        $scope = \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $this->model = $objectManager->getObject(
            Service::class
        );
    }

    public function testLsr()
    {
        $this->lsrMock
            ->method('getStoreConfig')
            ->with('ls_mag/service/base_url')
            ->willReturn($this->baseUrl);
        $service_type = new ServiceType( ServiceType::ECOMMERCE );
        $this->assertEquals($this->baseUrl.'/UCService.svc?singlewsdl', $this->model->getUrl($service_type,$this->baseUrl));
    }
}
