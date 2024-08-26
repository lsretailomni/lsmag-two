<?php

namespace Ls\Customer\Test\Unit\Observer;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Observer\SaveBefore;
use \Ls\Omni\Helper\ContactHelper;
use Magento\Customer\Model\Customer;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SaveBeforeObserverTest extends TestCase
{
    private const ID = '123';
    private const CUSTOMER_EMAIL = 'test@test.com';
    private $contactHelperMock;
    private $loggerMock;
    private $lsrMock;
    private $controllerMock;
    private $saveBeforeObserverMock;
    private $requestMock;
    private $responseMock;
    private $eventMock;
    private $dataObject;

    public function setUp(): void
    {
        $this->requestMock       = $this->createMock(HttpRequest::class);
        $this->responseMock      = $this->createMock(HttpResponse::class);
        $this->contactHelperMock = $this->createMock(ContactHelper::class);
        $this->loggerMock        = $this->createMock(LoggerInterface::class);
        $this->lsrMock           = $this->createConfiguredMock(
            LSR::class,
            [
                'getStoreConfig'    => self::ID,
                'getCurrentStoreId' => self::ID
            ]
        );
        $this->eventMock         = $this->createMock(Event::class);
        $this->controllerMock    = $this->createConfiguredMock(
            Action::class,
            [
                'getRequest'  => $this->requestMock,
                'getResponse' => $this->responseMock
            ]
        );
        $this->dataObject        = $this->createMock(Customer::class);
        $this->dataObject
            ->expects($this->any())
            ->method('setData')
            ->willReturnSelf();
        $this->eventMock
            ->expects($this->any())->method('__call')
            ->willReturnMap([
                ['getDataObject', [], $this->dataObject]
            ]);
        $this->responseMock
            ->expects($this->any())
            ->method('setRedirect');
        $this->lsrMock->expects($this->any())->method('getCurrentStoreId')->willReturn(self::ID);
        $this->lsrMock->expects($this->any())->method('getStoreConfig')->willReturn(self::ID);
        $this->contactHelperMock->expects($this->any())->method('encryptPassword')->willReturn(self::ID);
        $this->saveBeforeObserverMock = new SaveBefore($this->contactHelperMock, $this->loggerMock, $this->lsrMock);
    }

    public function testExecuteWithoutPasswordHashAndLsPasswordAndLsrCardId(): void
    {
        $this->saveBeforeObserverMock->execute(
            new Observer(
                [
                    'controller_action' => $this->controllerMock,
                    'request'           => $this->requestMock,
                    'event'             => $this->eventMock
                ]
            )
        );
    }

    public function testExecuteWithPasswordHashAndLsPasswordAndLsrCardIdAndLsrDown(): void
    {
        $this->lsrMock->expects($this->any())->method('isLSR')->willReturn(false);

        $this->dataObject
            ->expects($this->any())
            ->method('getData')
            ->willReturnMap([
                ['ls_password', null, self::ID],
                ['ls_validation', null, true],
                ['lsr_cardid', null, self::ID],
                ['email', null, self::CUSTOMER_EMAIL]
            ]);

        $this->saveBeforeObserverMock->execute(
            new Observer(
                [
                    'controller_action' => $this->controllerMock,
                    'request'           => $this->requestMock,
                    'event'             => $this->eventMock
                ]
            )
        );
    }

    public function testExecuteWithPasswordHashAndLsPasswordAndLsrCardIdAndWithoutEmail(): void
    {
        $this->expectException(InputException::class);
        $this->lsrMock->expects($this->any())->method('isLSR')->willReturn(true);
        $this->contactHelperMock->expects($this->any())->method('isEmailExistInLsCentral')->willReturn(true);
        $this->dataObject
            ->expects($this->any())
            ->method('getData')
            ->willReturnMap([
                ['ls_password', null, self::ID],
                ['ls_validation', null, true],
                ['lsr_cardid', null, self::ID]
            ]);

        $this->saveBeforeObserverMock->execute(
            new Observer(
                [
                    'controller_action' => $this->controllerMock,
                    'request'           => $this->requestMock,
                    'event'             => $this->eventMock
                ]
            )
        );
    }

    public function testExecuteWithPasswordHashAndLsPasswordAndLsrCardIdAndIsEmailExistInLsCentralFalse(): void
    {
        $this->lsrMock->expects($this->any())->method('isLSR')->willReturn(true);
        $this->contactHelperMock->expects($this->any())->method('isEmailExistInLsCentral')->willReturn(false);

        $this->dataObject
            ->expects($this->any())
            ->method('getData')
            ->willReturnMap([
                ['ls_password', null, self::ID],
                ['ls_validation', null, true],
                ['lsr_cardid', null, self::ID],
                ['email', null, self::CUSTOMER_EMAIL]
            ]);

        $this->saveBeforeObserverMock->execute(
            new Observer(
                [
                    'controller_action' => $this->controllerMock,
                    'request'           => $this->requestMock,
                    'event'             => $this->eventMock
                ]
            )
        );
    }

    public function testExecuteWithPasswordHashAndLsPasswordAndLsrCardIdAndIsEmailExistInLsCentralTrue(): void
    {
        $this->expectException(AlreadyExistsException::class);
        $this->lsrMock->expects($this->any())->method('isLSR')->willReturn(true);
        $this->contactHelperMock->expects($this->any())->method('isEmailExistInLsCentral')->willReturn(true);
        $this->dataObject
            ->expects($this->any())
            ->method('getData')
            ->willReturnMap([
                ['ls_password', null, self::ID],
                ['ls_validation', null, true],
                ['lsr_cardid', null, self::ID],
                ['email', null, self::CUSTOMER_EMAIL]
            ]);

        $this->saveBeforeObserverMock->execute(
            new Observer(
                [
                    'controller_action' => $this->controllerMock,
                    'request'           => $this->requestMock,
                    'event'             => $this->eventMock
                ]
            )
        );
    }
}
