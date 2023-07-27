<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

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
use PHPUnit\Framework\TestCase;
use Laminas\Uri\UriFactory;

class ContactGetByCardIdTest extends TestCase
{
    protected function setUp(): void
    {
        $baseUrl      = getenv('BASE_URL');
        $url          = implode('/', [$baseUrl, 'UCService.svc?singlewsdl']);
        $service_type = new ServiceType(ServiceType::ECOMMERCE);
        $uri          = UriFactory::factory($url);
        $this->client = new OmniClient($uri, $service_type);
        $this->client->setClassmap(ClassMap::getClassMap());
    }

    public function testExecute()
    {
        $this->assertNotNull($this->client);
        $param    = [
            'cardId' => getenv('CARD_ID')
        ];
        $response = $this->client->ContactGetByCardId($param);
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
    }
}
