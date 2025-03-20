<?php

namespace Ls\Customer\Test\Unit\Observer;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Observer\RegisterObserver;
use \Ls\Omni\Client\Ecommerce\Entity\MemberContact;
use \Ls\Omni\Helper\ContactHelper;
use Magento\Customer\Model\ResourceModel\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\Event\Observer;
use Magento\Framework\Registry;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class RegisterObserverTest extends TestCase
{
    private const ID = '123';
    private const CUSTOMER_EMAIL = 'test@test.com';
    private $contactHelperMock;
    private $registryMock;
    private $loggerMock;
    private $customerSessionMock;
    private $customerResourceModelMock;
    private $lsrMock;
    private $controllerMock;
    private $registerObserverMock;
    private $requestMock;
    private $responseMock;
    private $memberContact;
    private $customerMock;

    public function setUp(): void
    {
        $this->requestMock               = $this->createMock(HttpRequest::class);
        $this->responseMock              = $this->createMock(HttpResponse::class);
        $this->contactHelperMock         = $this->createMock(ContactHelper::class);
        $this->registryMock              = $this->createMock(Registry::class);
        $this->loggerMock                = $this->createMock(LoggerInterface::class);
        $this->customerSessionMock       = $this->createMock(CustomerSession::class);
        $this->customerResourceModelMock = $this->createMock(Customer::class);
        $this->lsrMock                   = $this->createMock(LSR::class);
        $this->controllerMock            = $this->createMock(Action::class);
        $this->memberContact             = $this->createMock(MemberContact::class);
        $this->customerMock              = $this->createMock(\Magento\Customer\Model\Customer::class);
        $this->customerMock->expects($this->any())
            ->method('getId')
            ->willReturn(
                self::ID
            );
        $this->customerMock->expects($this->any())
            ->method('getData')
            ->willReturn(
                [
                    ['id', null, self::ID],
                    ['entity_id', null, self::ID],
                    ['lsr_id', null, self::ID],
                    ['lsr_cardid', null, self::ID],
                    ['lsr_username', null, self::ID]
                ]
            );
        $this->controllerMock->expects($this->any())->method('getRequest')->willReturn($this->requestMock);
        $this->controllerMock->expects($this->any())->method('getResponse')->willReturn($this->responseMock);
        $this->memberContact->expects($this->any())->method('getUserName')->willReturn(self::ID);
        $this->memberContact->expects($this->any())->method('getEmail')->willReturn(self::CUSTOMER_EMAIL);

        $this->contactHelperMock
            ->expects($this->any())
            ->method('setCustomerAttributesValues')
            ->willReturn($this->customerMock);

        $this->contactHelperMock
            ->expects($this->any())
            ->method('getValue')
            ->willReturn([
                'email'        => self::CUSTOMER_EMAIL,
                'lsr_username' => self::ID,
                'password'     => self::ID,
                'lsr_id'       => self::ID,
                'contact'      => $this->memberContact
            ]);

        $this->lsrMock->expects($this->any())->method('getCurrentStoreId')->willReturn(self::ID);
        $this->registerObserverMock = new RegisterObserver(
            $this->contactHelperMock,
            $this->registryMock,
            $this->loggerMock,
            $this->customerSessionMock,
            $this->customerResourceModelMock,
            $this->lsrMock
        );
    }

    public function testExecuteWithLsrDown(): void
    {
        $this->lsrMock->expects($this->any())->method('isLSR')->willReturn(false);
        $this->customerSessionMock
            ->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customerMock);
        $this->registerObserverMock->execute(
            new Observer(['controller_action' => $this->controllerMock, 'request' => $this->requestMock])
        );
    }

    public function testExecuteWithCustomerFromSession(): void
    {
        $this->lsrMock->expects($this->any())->method('isLSR')->willReturn(true);
        $this->customerSessionMock
            ->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customerMock);
        $this->registerObserverMock->execute(
            new Observer(['controller_action' => $this->controllerMock, 'request' => $this->requestMock])
        );
    }

    public function testExecuteWithCustomerFromEmailAndLoginTrue(): void
    {
        $this->lsrMock->expects($this->any())->method('isLSR')->willReturn(true);
        $customerMock1 = $this->createMock(\Magento\Customer\Model\Customer::class);
        $this->customerSessionMock
            ->expects($this->any())
            ->method('getCustomer')
            ->willReturn($customerMock1);
        $this->contactHelperMock
            ->expects($this->any())
            ->method('getCustomerByEmail')
            ->willReturn($this->customerMock);

        $this->contactHelperMock
            ->expects($this->any())
            ->method('login')
            ->willReturn($this->memberContact);

        $this->registerObserverMock->execute(
            new Observer(['controller_action' => $this->controllerMock, 'request' => $this->requestMock])
        );
    }

    public function testExecuteWithCustomerFromEmailAndLoginFalse(): void
    {
        $this->lsrMock->expects($this->any())->method('isLSR')->willReturn(true);
        $customerMock1 = $this->createMock(\Magento\Customer\Model\Customer::class);
        $this->customerSessionMock
            ->expects($this->any())
            ->method('getCustomer')
            ->willReturn($customerMock1);
        $this->contactHelperMock
            ->expects($this->any())
            ->method('getCustomerByEmail')
            ->willReturn($this->customerMock);

        $this->registerObserverMock->execute(
            new Observer(['controller_action' => $this->controllerMock, 'request' => $this->requestMock])
        );
    }
}
