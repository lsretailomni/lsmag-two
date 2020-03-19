<?php

namespace Ls\Omni\Test\Customer;

use \Ls\Omni\Client\Ecommerce\ClassMap;
use \Ls\Omni\Client\Ecommerce\Entity\Account;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfAddress;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfCard;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfNotification;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfOneList;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfProfile;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfPublishedOffer;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfSalesEntry;
use \Ls\Omni\Client\Ecommerce\Entity\MemberContact;
use \Ls\Omni\Service\ServiceType;
use \Ls\Omni\Service\Soap\Client as OmniClient;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Model\Customer\NotificationStorage;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use PHPUnit\Framework\TestCase;
use Zend\Uri\UriFactory;

class CustomerRegistrationTest extends TestCase
{
    /** @var OmniClient */
    public $client;

    /**
     * @var \Magento\Customer\Model\CustomerFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerFactory;

    /**
     * @var \Magento\Customer\Model\Data\CustomerSecureFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerSecureFactory;

    /**
     * @var \Magento\Customer\Model\CustomerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerRegistry;

    /**
     * @var \Magento\Customer\Model\ResourceModel\AddressRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $addressRepository;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerResourceModel;

    /**
     * @var \Magento\Customer\Api\CustomerMetadataInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerMetadata;

    /**
     * @var \Magento\Customer\Api\Data\CustomerSearchResultsInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $searchResultsFactory;

    /**
     * @var \Magento\Framework\Event\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventManager;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Api\ExtensibleDataObjectConverter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $extensibleDataObjectConverter;

    /**
     * @var \Magento\Framework\Api\DataObjectHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dataObjectHelper;

    /**
     * @var \Magento\Framework\Api\ImageProcessorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $imageProcessor;

    /**
     * @var \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $extensionAttributesJoinProcessor;

    /**
     * @var \Magento\Customer\Api\Data\CustomerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $customer;

    /**
     * @var CollectionProcessorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $collectionProcessorMock;

    /**
     * @var \Magento\Customer\Model\ResourceModel\CustomerRepository
     */
    protected $model;

    /**
     * @var NotificationStorage
     */
    private $notificationStorage;

    protected function setUp()
    {
        $baseUrl      = $_ENV['BASE_URL'];
        $url          = implode('/', [$baseUrl, 'UCService.svc?singlewsdl']);
        $service_type = new ServiceType(ServiceType::ECOMMERCE);
        $uri          = UriFactory::factory($url);
        $this->client = new OmniClient($uri, $service_type);
        $this->client->setClassmap(ClassMap::getClassMap());
        $this->assertNotNull($this->client);
        $this->customerResourceModel            =
            $this->createMock(\Magento\Customer\Model\ResourceModel\Customer::class);
        $this->customerRegistry                 = $this->createMock(\Magento\Customer\Model\CustomerRegistry::class);
        $this->dataObjectHelper                 = $this->createMock(\Magento\Framework\Api\DataObjectHelper::class);
        $this->customerFactory                  =
            $this->createPartialMock(\Magento\Customer\Model\CustomerFactory::class, ['create']);
        $this->customerSecureFactory            = $this->createPartialMock(
            \Magento\Customer\Model\Data\CustomerSecureFactory::class,
            ['create']
        );
        $this->addressRepository                = $this->createMock(\Magento\Customer\Model\ResourceModel\AddressRepository::class);
        $this->customerMetadata                 = $this->getMockForAbstractClass(
            \Magento\Customer\Api\CustomerMetadataInterface::class,
            [],
            '',
            false
        );
        $this->searchResultsFactory             = $this->createPartialMock(
            \Magento\Customer\Api\Data\CustomerSearchResultsInterfaceFactory::class,
            ['create']
        );
        $this->eventManager                     = $this->getMockForAbstractClass(
            \Magento\Framework\Event\ManagerInterface::class,
            [],
            '',
            false
        );
        $this->storeManager                     = $this->getMockForAbstractClass(
            \Magento\Store\Model\StoreManagerInterface::class,
            [],
            '',
            false
        );
        $this->extensibleDataObjectConverter    = $this->createMock(
            \Magento\Framework\Api\ExtensibleDataObjectConverter::class
        );
        $this->imageProcessor                   = $this->getMockForAbstractClass(
            \Magento\Framework\Api\ImageProcessorInterface::class,
            [],
            '',
            false
        );
        $this->extensionAttributesJoinProcessor = $this->getMockForAbstractClass(
            \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface::class,
            [],
            '',
            false
        );
        $this->customer                         = $this->getMockForAbstractClass(
            \Magento\Customer\Api\Data\CustomerInterface::class,
            [],
            '',
            true,
            true,
            true,
            [
                '__toArray'
            ]
        );
        $this->collectionProcessorMock          = $this->getMockBuilder(CollectionProcessorInterface::class)
            ->getMock();
        $this->notificationStorage              = $this->getMockBuilder(NotificationStorage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new \Magento\Customer\Model\ResourceModel\CustomerRepository(
            $this->customerFactory,
            $this->customerSecureFactory,
            $this->customerRegistry,
            $this->addressRepository,
            $this->customerResourceModel,
            $this->customerMetadata,
            $this->searchResultsFactory,
            $this->eventManager,
            $this->storeManager,
            $this->extensibleDataObjectConverter,
            $this->dataObjectHelper,
            $this->imageProcessor,
            $this->extensionAttributesJoinProcessor,
            $this->collectionProcessorMock,
            $this->notificationStorage
        );
    }

    public function testCustomerRegistrationOmni()
    {
        $this->assertNotNull($this->client);
        $append      = "test1" . chr(rand(97, 122));
        $alternateId = 'LSM' . str_pad(md5(rand(500, 600) . $append . $_ENV['USERNAME']), 8, '0', STR_PAD_LEFT);
        $contact     = new MemberContact();
        $contact->setAlternateId($alternateId);
        $contact->setEmail($append . $_ENV['EMAIL']);
        $contact->setUserName($append . $_ENV['USERNAME']);
        $contact->setPassword($_ENV['PASSWORD']);
        $contact->setFirstName("test");
        $contact->setLastName("test");

        $contactCreate = new \Ls\Omni\Client\Ecommerce\Entity\ContactCreate();
        $contactCreate->setContact($contact);

        $response = $this->client->ContactCreate($contactCreate);
        $result   = $response->getResult();
        $this->assertInstanceOf(MemberContact::class, $result);
        $this->assertInstanceOf(ArrayOfAddress::class, $result->getAddresses());
        $this->assertInstanceOf(ArrayOfCard::class, $result->getCards());
        $this->assertInstanceOf(ArrayOfNotification::class, $result->getNotifications());
        $this->assertInstanceOf(ArrayOfOneList::class, $result->getOneLists());
        $this->assertInstanceOf(ArrayOfProfile::class, $result->getProfiles());
        $this->assertInstanceOf(ArrayOfPublishedOffer::class, $result->getPublishedOffers());
        $this->assertInstanceOf(ArrayOfSalesEntry::class, $result->getSalesEntries());
        $this->assertInstanceOf(Account::class, $result->getAccount());
        $this->assertNotNull($result->getUserName());
        $this->assertNotNull($result->getEmail());
        $this->assertNotNull($result->getId());
        return $result;
    }

    public function testCustomerRegistrationMagento()
    {
        /** @var MemberContact $contact */
        $contact = $this->testCustomerRegistrationOmni();
        if (is_object($contact) && $contact->getId()) {
            $customerId    = 1;
            $storeId       = 1;
            $websiteId     = 1;
            $customerModel = $this->createPartialMock(\Magento\Customer\Model\Customer::class, [
                'getId',
                'setId',
                'setData',
                'setStoreId',
                'getStoreId',
                'getAttributeSetId',
                'setAttributeSetId',
                'setRpToken',
                'setRpTokenCreatedAt',
                'getDataModel',
                'setPasswordHash',
                'setFailuresNum',
                'setFirstFailure',
                'setLockExpires',
                'save',
            ]);

            $origCustomer = $this->customer;

            $this->customer->expects($this->atLeastOnce())
                ->method('__toArray')
                ->willReturn(['default_billing', 'default_shipping']);

            $customerAttributesMetaData = $this->getMockForAbstractClass(
                \Magento\Framework\Api\CustomAttributesDataInterface::class,
                [],
                '',
                false,
                false,
                true,
                [
                    'getId',
                    'getEmail',
                    'getWebsiteId',
                    'getAddresses',
                    'setAddresses'
                ]
            );
            $customerSecureData         = $this->createPartialMock(\Magento\Customer\Model\Data\CustomerSecure::class, [
                'getRpToken',
                'getRpTokenCreatedAt',
                'getPasswordHash',
                'getFailuresNum',
                'getFirstFailure',
                'getLockExpires',
            ]);
            $this->customer->expects($this->atLeastOnce())
                ->method('getId')
                ->willReturn($customerId);
            $this->customerRegistry->expects($this->atLeastOnce())
                ->method('retrieve')
                ->with($customerId)
                ->willReturn($customerModel);
            $customerModel->expects($this->atLeastOnce())
                ->method('getDataModel')
                ->willReturn($this->customer);
            $this->imageProcessor->expects($this->once())
                ->method('save')
                ->with($this->customer, CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER, $this->customer)
                ->willReturn($customerAttributesMetaData);
            $this->customerRegistry->expects($this->atLeastOnce())
                ->method("remove")
                ->with($customerId);
            $this->extensibleDataObjectConverter->expects($this->once())
                ->method('toNestedArray')
                ->with($customerAttributesMetaData, [], \Magento\Customer\Api\Data\CustomerInterface::class)
                ->willReturn(['customerData']);
            $this->customerFactory->expects($this->once())
                ->method('create')
                ->with(['data' => ['customerData']])
                ->willReturn($customerModel);
            $customerModel->expects($this->once())
                ->method('getStoreId')
                ->willReturn(null);
            $store = $this->createMock(\Magento\Store\Model\Store::class);
            $store->expects($this->once())
                ->method('getId')
                ->willReturn($storeId);
            $this->storeManager
                ->expects($this->once())
                ->method('getStore')
                ->willReturn($store);
            $customerModel->expects($this->once())
                ->method('setStoreId')
                ->with($storeId);
            $customerModel->expects($this->once())
                ->method('setId')
                ->with($customerId);
            $customerModel->method('setData')
                ->with('lsr_id', $contact->getId());
            $customerModel->method('setData')
                ->with('lsr_token', $contact->getLoggedOnToDevice()->getSecurityToken());
            $customerModel->method('setData')
                ->with('lsr_cardid', $contact->getCards()->getCard()[0]->getId());
            $customerModel->expects($this->once())
                ->method('getAttributeSetId')
                ->willReturn(null);
            $customerModel->expects($this->once())
                ->method('setAttributeSetId')
                ->with(\Magento\Customer\Api\CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER);
            $customerAttributesMetaData->expects($this->atLeastOnce())
                ->method('getId')
                ->willReturn($customerId);
            $this->customerRegistry->expects($this->once())
                ->method('retrieveSecureData')
                ->with($customerId)
                ->willReturn($customerSecureData);
            $customerSecureData->expects($this->once())
                ->method('getRpToken')
                ->willReturn('rpToken');
            $customerSecureData->expects($this->once())
                ->method('getRpTokenCreatedAt')
                ->willReturn('rpTokenCreatedAt');
            $customerSecureData->expects($this->once())
                ->method('getPasswordHash')
                ->willReturn('passwordHash');
            $customerSecureData->expects($this->once())
                ->method('getFailuresNum')
                ->willReturn('failuresNum');
            $customerSecureData->expects($this->once())
                ->method('getFirstFailure')
                ->willReturn('firstFailure');
            $customerSecureData->expects($this->once())
                ->method('getLockExpires')
                ->willReturn('lockExpires');

            $customerModel->expects($this->once())
                ->method('setRpToken')
                ->willReturnMap([
                    ['rpToken', $customerModel],
                    [null, $customerModel],
                ]);
            $customerModel->expects($this->once())
                ->method('setRpTokenCreatedAt')
                ->willReturnMap([
                    ['rpTokenCreatedAt', $customerModel],
                    [null, $customerModel],
                ]);

            $customerModel->expects($this->once())
                ->method('setPasswordHash')
                ->with('passwordHash');
            $customerModel->expects($this->once())
                ->method('setFailuresNum')
                ->with('failuresNum');
            $customerModel->expects($this->once())
                ->method('setFirstFailure')
                ->with('firstFailure');
            $customerModel->expects($this->once())
                ->method('setLockExpires')
                ->with('lockExpires');
            $customerModel->expects($this->atLeastOnce())
                ->method('getId')
                ->willReturn($customerId);
            $customerModel->expects($this->once())
                ->method('save');
            $this->customerRegistry->expects($this->once())
                ->method('push')
                ->with($customerModel);
            $customerAttributesMetaData->expects($this->once())
                ->method('getEmail')
                ->willReturn($contact->getEmail());
            $customerAttributesMetaData->expects($this->once())
                ->method('getWebsiteId')
                ->willReturn($websiteId);
            $this->customerRegistry->expects($this->once())
                ->method('retrieveByEmail')
                ->with($contact->getEmail(), $storeId)
                ->willReturn($customerModel);
            $this->model->save($this->customer);
        }
    }
}