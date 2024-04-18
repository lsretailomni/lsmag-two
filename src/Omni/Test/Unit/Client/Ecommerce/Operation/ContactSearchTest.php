<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Entity\ContactSearchResponse;

class ContactSearchTest extends OmniClientSetupTest
{
    public function testSearchUsername()
    {
        $username = $this->getEnvironmentVariableValueGivenName('USERNAME');

        $this->assertNotNull($this->client);
        $params   = [
            'searchType' => Entity\Enum\ContactSearchType::USER_NAME,
            'search'     => $username
        ];
        $response = $this->executeMethod("ContactSearch", $params);
        $this->assertInstanceOf(ContactSearchResponse::class, $response);
    }

    public function testSearchEmail()
    {
        $email    = $this->getEnvironmentVariableValueGivenName('EMAIL');

        $this->assertNotNull($this->client);
        $params   = [
            'searchType' => Entity\Enum\ContactSearchType::EMAIL,
            'search'     => $email
        ];
        $response = $this->executeMethod("ContactSearch", $params);
        $this->assertInstanceOf(ContactSearchResponse::class, $response);
    }
}
