<?php

namespace Ls\Omni\Test\Unit\Service;

class ServiceTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var \Magento\Framework\App\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $config;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $_objectManager;

    /**
     * @var \Ls\Core\Model\LSR
     */
    protected $lsr;

    const BASE_URL = 'http://10.27.9.39/LSOmniService411';

    protected function setUp()
    {
        $this->config = $this->createMock(\Magento\Framework\App\Config::class);
        $scope = \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $this->_objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
    }

    public function testLsr()
    {
        $lsrMock = $this->createMock(\Ls\Core\Model\LSR::class);
        $lsrMock->method('getStoreConfig')
            ->with('ls_mag/service/base_url')
            ->willReturn(self::BASE_URL);
        $this->assertEquals(self::BASE_URL, $lsrMock->getStoreConfig('ls_mag/service/base_url'));
    }
}