<?php

namespace Ls\Customer\Test\Unit\Observer;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Observer\ResetPasswordObserver;
use \Ls\Omni\Helper\ContactHelper;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Message\ManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ResetPasswordObserverTest extends TestCase
{
    private $contactHelperMock;
    private $messageManagerMock;
    private $loggerMock;
    private $customerSessionMock;
    private $actionFlagMock;
    private $redirectInterfaceMock;
    private $lsrMock;
    private $customerFactoryMock;
    private $resetPasswordObserverMock;
    private $requestMock;
    private $customerMock;
    private $responseMock;
    private $controllerMock;

    public function setUp(): void
    {
        $this->requestMock           = $this->createMock(HttpRequest::class);
        $this->responseMock          = $this->createMock(HttpResponse::class);
        $this->contactHelperMock     = $this->createMock(ContactHelper::class);
        $this->messageManagerMock    = $this->createMock(ManagerInterface::class);
        $this->loggerMock            = $this->createMock(LoggerInterface::class);
        $this->customerSessionMock   = $this->createMock(CustomerSession::class);
        $this->redirectInterfaceMock = $this->createMock(RedirectInterface::class);
        $this->actionFlagMock        = $this->createMock(ActionFlag::class);
        $this->lsrMock               = $this->createMock(LSR::class);
        $this->controllerMock        = $this->createMock(Action::class);
        $this->customerFactoryMock   = $this->createMock(CustomerFactory::class);
        $this->customerMock          = $this->createMock(Customer::class);
        $this->customerMock
            ->expects($this->any())
            ->method('getData')
            ->willReturnMap(
                [
                    ['lsr_username', null, 'username'],
                    ['lsr_resetcode', null, 'resetcode']
                ]
            );

        $this->customerFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->customerMock);

        $this->requestMock
            ->expects($this->any())
            ->method('getQuery')
            ->with('id')
            ->willReturn(1);
        $this->requestMock
            ->expects($this->any())
            ->method('getParams')
            ->willReturn(['password' => '123', 'password_confirmation' => '123']);
        $this->redirectInterfaceMock
            ->expects($this->any())
            ->method('getRefererUrl')
            ->willReturn('http://localhost:8080');
        $this->responseMock
            ->expects($this->any())
            ->method('setRedirect');

        $this->controllerMock->expects($this->any())->method('getRequest')->willReturn($this->requestMock);
        $this->controllerMock->expects($this->any())->method('getResponse')->willReturn($this->responseMock);

        $this->lsrMock->expects($this->any())->method('getCurrentStoreId')->willReturn(1);
        $this->contactHelperMock
            ->expects($this->any())
            ->method('resetPassword')
            ->with($this->customerMock, $this->requestMock->getParams())
            ->willReturn(true);

        $this->resetPasswordObserverMock = new ResetPasswordObserver(
            $this->contactHelperMock,
            $this->messageManagerMock,
            $this->loggerMock,
            $this->customerSessionMock,
            $this->redirectInterfaceMock,
            $this->actionFlagMock,
            $this->customerFactoryMock,
            $this->lsrMock
        );
    }

    public function testExecuteWithLsrDown(): void
    {
        $this->lsrMock->expects($this->any())->method('isLSR')->willReturn(false);
        $this->resetPasswordObserverMock->execute(
            new Observer(['controller_action' => $this->controllerMock])
        );
    }

    public function testExecuteWithLsrUpAndWithRpToken(): void
    {
        $this->lsrMock->expects($this->any())->method('isLSR')->willReturn(true);
        $this->customerSessionMock->expects($this->any())->method('__call')
            ->willReturnMap([
                ['getRpToken', [], '124']
            ]);
        $this->resetPasswordObserverMock->execute(
            new Observer(['controller_action' => $this->controllerMock])
        );
    }

    public function testExecuteWithLsrUpAndWithoutRpTokenAndWithoutCustomer(): void
    {
        $this->lsrMock->expects($this->any())->method('isLSR')->willReturn(true);
        $this->resetPasswordObserverMock->execute(
            new Observer(['controller_action' => $this->controllerMock])
        );
    }

    public function testExecuteWithLsrUpAndWithoutRpToken(): void
    {
        $this->lsrMock->expects($this->any())->method('isLSR')->willReturn(true);
        $this->customerMock->expects($this->any())
            ->method('load')
            ->willReturnSelf();
        $this->resetPasswordObserverMock->execute(
            new Observer(['controller_action' => $this->controllerMock])
        );
    }
}
