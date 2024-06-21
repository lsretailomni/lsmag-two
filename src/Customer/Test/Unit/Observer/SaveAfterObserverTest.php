<?php

namespace Ls\Customer\Test\Unit\Observer;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Observer\SaveAfter;
use \Ls\Omni\Client\Ecommerce\Entity\MemberContact;
use \Ls\Omni\Helper\ContactHelper;
use Magento\Customer\Model\Customer;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SaveAfterObserverTest extends TestCase
{
    private const ID = '123';
    private const CUSTOMER_EMAIL = 'test@test.com';
    private $contactHelperMock;
    private $loggerMock;
    private $lsrMock;
    private $customerResourceModelMock;
    private $controllerMock;
    private $saveafterObserverMock;
    private $requestMock;
    private $responseMock;
    private $eventMock;
    private $customerMock;
    private $memberContact;

    public function setUp(): void
    {
        $this->requestMock               = $this->createMock(HttpRequest::class);
        $this->responseMock              = $this->createMock(HttpResponse::class);
        $this->contactHelperMock         = $this->createMock(ContactHelper::class);
        $this->loggerMock                = $this->createMock(LoggerInterface::class);
        $this->customerResourceModelMock = $this->createMock(\Magento\Customer\Model\ResourceModel\Customer::class);
        $this->memberContact             = $this->createMock(MemberContact::class);
        $this->lsrMock                   = $this->createConfiguredMock(
            LSR::class,
            [
                'getStoreConfig'    => self::ID,
                'getCurrentStoreId' => self::ID
            ]
        );
        $this->eventMock                 = $this->createMock(Event::class);
        $this->controllerMock            = $this->createConfiguredMock(
            Action::class,
            [
                'getRequest'  => $this->requestMock,
                'getResponse' => $this->responseMock
            ]
        );
        $this->customerMock              = $this->createMock(Customer::class);
        $this->customerMock->expects($this->any())
            ->method('getId')
            ->willReturn(
                self::ID
            );

        $this->customerMock
            ->expects($this->any())
            ->method('setData')
            ->willReturnSelf();
        $this->eventMock
            ->expects($this->any())->method('__call')
            ->willReturnMap([
                ['getCustomer', [], $this->customerMock]
            ]);
        $this->lsrMock->expects($this->any())->method('getCurrentStoreId')->willReturn(self::ID);
        $this->saveafterObserverMock = new SaveAfter(
            $this->contactHelperMock,
            $this->loggerMock,
            $this->customerResourceModelMock,
            $this->lsrMock
        );
    }

    public function testExecuteWithLsrDown(): void
    {
        $this->lsrMock->expects($this->any())->method('isLSR')->willReturn(false);
        $this->contactHelperMock->expects($this->any())->method('isUsernameExist')->willReturn(false);
        $this->contactHelperMock->expects($this->any())->method('generateRandomUsername')->willReturn(self::ID);
        $this->customerMock->expects($this->any())
            ->method('getData')
            ->willReturnMap(
                [
                    ['lsr_username', null, self::ID],
                    ['ls_password', null, self::ID]
                ]
            );
        $this->saveafterObserverMock->execute(
            new Observer(
                [
                    'controller_action' => $this->controllerMock,
                    'request'           => $this->requestMock,
                    'event'             => $this->eventMock
                ]
            )
        );
    }

    public function testExecuteWithoutLsPassword(): void
    {
        $this->customerMock->expects($this->any())
            ->method('getData');
        $this->saveafterObserverMock->execute(
            new Observer(
                [
                    'controller_action' => $this->controllerMock,
                    'request'           => $this->requestMock,
                    'event'             => $this->eventMock
                ]
            )
        );
    }

    public function testExecuteWithLsPasswordUsernameExistsFalseAndExistingContact(): void
    {
        $this->lsrMock->expects($this->any())->method('isLSR')->willReturn(true);
        $this->contactHelperMock->expects($this->any())->method('generateRandomUsername')->willReturn(self::ID);
        $this->contactHelperMock->expects($this->any())->method('isUsernameExist')->willReturn(false);
        $this->contactHelperMock->expects($this->any())->method('isUsernameExistInLsCentral')->willReturn(false);
        $this->memberContact->expects($this->any())
            ->method('getId')
            ->willReturn(
                self::ID
            );
        $this->contactHelperMock
            ->expects($this->any())
            ->method('getCustomerByUsernameOrEmailFromLsCentral')
            ->willReturn($this->memberContact);
        $this->contactHelperMock
            ->expects($this->any())
            ->method('setCustomerAttributesValues')
            ->willReturn($this->customerMock);
        $this->contactHelperMock
            ->expects($this->any())
            ->method('forgotPassword')
            ->willReturn(self::ID);
        $this->customerMock->expects($this->any())
            ->method('getData')
            ->willReturnMap(
                [
                    ['lsr_username', null, self::ID],
                    ['ls_password', null, self::ID]
                ]
            );
        $this->saveafterObserverMock->execute(
            new Observer(
                [
                    'controller_action' => $this->controllerMock,
                    'request'           => $this->requestMock,
                    'event'             => $this->eventMock
                ]
            )
        );
    }

    public function testExecuteWithLsPasswordUsernameExistsFalseAndNonExistingContact(): void
    {
        $this->lsrMock->expects($this->any())->method('isLSR')->willReturn(true);
        $this->contactHelperMock->expects($this->any())->method('generateRandomUsername')->willReturn(self::ID);
        $this->contactHelperMock->expects($this->any())->method('isUsernameExist')->willReturn(false);
        $this->contactHelperMock->expects($this->any())->method('isUsernameExistInLsCentral')->willReturn(false);
        $this->memberContact->expects($this->any())
            ->method('getId')
            ->willReturn(
                self::ID
            );
        $this->contactHelperMock
            ->expects($this->any())
            ->method('contact')
            ->willReturn($this->memberContact);
        $this->contactHelperMock
            ->expects($this->any())
            ->method('setCustomerAttributesValues')
            ->willReturn($this->customerMock);
        $this->contactHelperMock
            ->expects($this->any())
            ->method('forgotPassword')
            ->willReturn(self::ID);
        $this->customerMock->expects($this->any())
            ->method('getData')
            ->willReturnMap(
                [
                    ['lsr_username', null, self::ID],
                    ['ls_password', null, self::ID]
                ]
            );
        $this->saveafterObserverMock->execute(
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
