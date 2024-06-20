<?php

namespace Ls\Customer\Test\Unit\Observer;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Observer\LoginObserver;
use \Ls\Omni\Client\Ecommerce\Entity\MemberContact;
use \Ls\Omni\Helper\ContactHelper;
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

class LoginObserverTest extends TestCase
{
    private const ID = '123';
    private const CUSTOMER_EMAIL = 'test@test.com';
    private $contactHelperMock;
    private $messageManagerMock;
    private $loggerMock;
    private $customerSessionMock;
    private $redirectInterfaceMock;
    private $actionFlagMock;
    private $lsrMock;
    private $controllerMock;
    private $loginObserverMock;
    private $requestMock;
    private $responseMock;
    private $memberContact;

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
        $this->memberContact         = $this->createMock(MemberContact::class);
        $this->requestMock
            ->expects($this->any())
            ->method('getPost')
            ->with('login')
            ->willReturn(['username' => self::ID, 'password' => self::ID]);
        $this->redirectInterfaceMock
            ->expects($this->any())
            ->method('getRefererUrl')
            ->willReturn('http://localhost:8080');
        $this->responseMock
            ->expects($this->any())
            ->method('setRedirect');
        $this->controllerMock->expects($this->any())->method('getRequest')->willReturn($this->requestMock);
        $this->controllerMock->expects($this->any())->method('getResponse')->willReturn($this->responseMock);
        $this->memberContact->expects($this->any())->method('getUserName')->willReturn(self::ID);
        $this->memberContact->expects($this->any())->method('getEmail')->willReturn(self::CUSTOMER_EMAIL);
        $this->lsrMock->expects($this->any())->method('getCurrentStoreId')->willReturn(self::ID);
        $this->loginObserverMock = new LoginObserver(
            $this->contactHelperMock,
            $this->messageManagerMock,
            $this->loggerMock,
            $this->customerSessionMock,
            $this->redirectInterfaceMock,
            $this->actionFlagMock,
            $this->lsrMock
        );
    }

    public function testExecuteWithLsrDown(): void
    {
        $this->lsrMock->expects($this->any())->method('isLSR')->willReturn(false);
        $this->loginObserverMock->execute(
            new Observer(['controller_action' => $this->controllerMock, 'request' => $this->requestMock])
        );
    }

    public function testExecuteWithUsername(): void
    {
        $this->lsrMock->expects($this->any())->method('isLSR')->willReturn(true);
        $this->contactHelperMock->expects($this->any())->method('isValid')->with(self::ID)->willReturn(false);

        $this->loginObserverMock->execute(
            new Observer(['controller_action' => $this->controllerMock, 'request' => $this->requestMock])
        );
    }

    public function testExecuteWithEmailAndLoginTrue(): void
    {
        $this->lsrMock->expects($this->any())->method('isLSR')->willReturn(true);
        $this->contactHelperMock->expects($this->any())->method('isValid')->with(self::ID)->willReturn(true);
        $this->contactHelperMock->expects($this->any())->method('search')->with(self::ID)->willReturn(
            $this->memberContact
        );
        $this->contactHelperMock->expects($this->any())->method('login')->willReturn(
            true
        );
        $this->loginObserverMock->execute(
            new Observer(['controller_action' => $this->controllerMock, 'request' => $this->requestMock])
        );
    }

    public function testExecuteWithEmailAndSearchFalse(): void
    {
        $this->lsrMock->expects($this->any())->method('isLSR')->willReturn(true);
        $this->contactHelperMock->expects($this->any())->method('isValid')->with(self::ID)->willReturn(true);
        $this->contactHelperMock->expects($this->any())->method('login')->willReturn(
            true
        );
        $this->loginObserverMock->execute(
            new Observer(['controller_action' => $this->controllerMock, 'request' => $this->requestMock])
        );
    }

    public function testExecuteWithEmailAndLoginMemberContact(): void
    {
        $this->lsrMock->expects($this->any())->method('isLSR')->willReturn(true);
        $this->contactHelperMock->expects($this->any())->method('isValid')->with(self::ID)->willReturn(true);
        $this->contactHelperMock->expects($this->any())->method('search')->with(self::ID)->willReturn(
            $this->memberContact
        );
        $this->contactHelperMock->expects($this->any())->method('login')->willReturn(
            $this->memberContact
        );
        $this->loginObserverMock->execute(
            new Observer(['controller_action' => $this->controllerMock, 'request' => $this->requestMock])
        );
    }
}
