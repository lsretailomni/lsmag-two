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
use \Ls\Omni\Client\Ecommerce\Entity\ContactCreate;
use \Ls\Omni\Client\Ecommerce\Entity\MemberContact;
use \Ls\Omni\Helper\ContactHelper;
use \Ls\Omni\Service\ServiceType;
use \Ls\Omni\Service\Soap\Client as OmniClient;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Laminas\Uri\UriFactory;

class CustomerRegistrationTest extends \PHPUnit\Framework\TestCase
{
    /** @var OmniClient */
    public $client;

    public $attributeRepository;

    public $contactHelper;

    protected function setUp(): void
    {
        $baseUrl      = getenv('BASE_URL');
        $url          = implode('/', [$baseUrl, 'UCService.svc?singlewsdl']);
        $service_type = new ServiceType(ServiceType::ECOMMERCE);
        $uri          = UriFactory::factory($url);
        $this->client = new OmniClient($uri, $service_type);
        $this->client->setClassmap(ClassMap::getClassMap());
        $this->assertNotNull($this->client);

        $this->attributeRepository = Bootstrap::getObjectManager()->get(AttributeRepositoryInterface::class);
        $this->contactHelper       = Bootstrap::getObjectManager()->get(ContactHelper::class);
    }

    protected function customerRegistrationOmni()
    {
        $this->assertNotNull($this->client);
        $append      = "test4" . chr(rand(97, 122));
        $alternateId = 'LSM' . str_pad(md5(rand(500, 600) . $append . $_ENV['USERNAME']), 8, '0', STR_PAD_LEFT);
        $contact     = new MemberContact();
        $contact->setAlternateId($alternateId);
        $contact->setEmail($append . $_ENV['EMAIL']);
        $contact->setUserName($append . $_ENV['USERNAME']);
        $contact->setPassword($_ENV['PASSWORD']);
        $contact->setFirstName("test");
        $contact->setLastName("test");

        $contactCreate = new ContactCreate();
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

    public function testCustomerRegistration()
    {
        $contact           = $this->customerRegistrationOmni();
        $attributeUsername = $this->attributeRepository->get('customer', 'lsr_username');
        $this->assertEquals('lsr_username', $attributeUsername->getAttributeCode());
        $attributeLsrId = $this->attributeRepository->get('customer', 'lsr_id');
        $this->assertEquals('lsr_id', $attributeLsrId->getAttributeCode());
        $attributeLsrToken = $this->attributeRepository->get('customer', 'lsr_token');
        $this->assertEquals('lsr_token', $attributeLsrToken->getAttributeCode());
        $attributeLsrCardId = $this->attributeRepository->get('customer', 'lsr_cardid');
        $this->assertEquals('lsr_cardid', $attributeLsrCardId->getAttributeCode());
        $this->getCustomerGroupIdByName($contact->getAccount()->getScheme()->getId());
    }

    protected function getCustomerGroupIdByName($groupName)
    {
        $customerGroupId = $this->contactHelper->getCustomerGroupIdByName($groupName);
        $this->assertNotNull($customerGroupId);
    }
}
