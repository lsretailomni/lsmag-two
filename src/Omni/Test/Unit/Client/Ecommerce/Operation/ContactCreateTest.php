<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

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

class ContactCreateTest extends OmniClientSetupTest
{
    public function testExecute()
    {
        $this->assertNotNull($this->client);
        $append      = 'test' . substr(md5(uniqid(rand(), true)), 0, 5);
        $alternateId = 'LSM' . str_pad(md5(rand(500, 600) . $append . $this->getEnvironmentVariableValueGivenName('USERNAME')), 8, '0', STR_PAD_LEFT);
        $contact     = new MemberContact();
        $contact->setAlternateId($alternateId);
        $contact->setEmail($append . $this->getEnvironmentVariableValueGivenName('EMAIL'));
        $contact->setUserName($append . $this->getEnvironmentVariableValueGivenName('USERNAME'));
        $contact->setPassword($this->getEnvironmentVariableValueGivenName('PASSWORD'));
        $contact->setFirstName("test");
        $contact->setLastName("test");

        $contactCreate = new ContactCreate();
        $contactCreate->setContact($contact);
        $contactCreate->setDoLogin(1);
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
    }
}
