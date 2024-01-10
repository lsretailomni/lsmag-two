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
use \Ls\Omni\Client\Ecommerce\Entity\Card;
use \Ls\Omni\Client\Ecommerce\Entity\ContactUpdate;
use \Ls\Omni\Client\Ecommerce\Entity\MemberContact;

class ContactUpdateTest extends OmniClientSetupTest
{
    public function testExecute()
    {
        $this->assertNotNull($this->client);
        $contact = new MemberContact();
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $append  = "test" . chr(rand(97, 122));
        $contact->setFirstName($append . "test");
        $contact->setLastName($append . "test");
        $contact->setId($this->getEnvironmentVariableValueGivenName('CONTACT_ID'));
        $contact->setUserName($this->getEnvironmentVariableValueGivenName('USERNAME'));
        $contact->setEmail($this->getEnvironmentVariableValueGivenName('EMAIL'));
        $contact->setMiddleName('  ');
        $card = new Card();
        $card->setId($this->getEnvironmentVariableValueGivenName('CARD_ID'));
        $cardArray = new ArrayOfCard();
        $cardArray->setCard($card);
        $contact->setCards($cardArray);
        $contactUpdate = new ContactUpdate();
        $contactUpdate->setContact($contact);
        $contactUpdate->setGetContact(1);
        $response = $this->client->ContactUpdate($contactUpdate);
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
