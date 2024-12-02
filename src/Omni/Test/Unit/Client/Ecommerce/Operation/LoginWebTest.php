<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfMemberContact;
use \Ls\Omni\Client\Ecommerce\Entity\ContactSearchResponse;
use \Ls\Omni\Client\Ecommerce\Entity\LoginWebResponse;

class LoginWebTest extends OmniClientSetupTest
{
    public function testSearchUsername()
    {
        $username = $this->getEnvironmentVariableValueGivenName('USERNAME');
        $params   = [
            'searchType' => Entity\Enum\ContactSearchType::USER_NAME,
            'search'     => $username
        ];

        $response = $this->executeMethod("ContactSearch", $params);
        $this->assertInstanceOf(ContactSearchResponse::class, $response);
        $this->assertInstanceOf(ArrayOfMemberContact::class, $response->getResult());
        $this->assertGreaterThanOrEqual(1, count($response->getResult()->getMemberContact()));
    }

    /**
     * @depends testSearchUsername
     */
    public function testLoginUserName()
    {
        $username = $this->getEnvironmentVariableValueGivenName('USERNAME');
        $password = $this->getEnvironmentVariableValueGivenName('PASSWORD');
        $params   = [
            'userName' => $username,
            'password' => $password
        ];
        $response = $this->executeMethod("LoginWeb", $params);
        $this->assertInstanceOf(LoginWebResponse::class, $response);
    }
}
