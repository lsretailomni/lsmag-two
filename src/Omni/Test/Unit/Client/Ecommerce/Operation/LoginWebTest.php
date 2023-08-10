<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfMemberContact;
use \Ls\Omni\Client\Ecommerce\Entity\ContactSearchResponse;
use \Ls\Omni\Client\Ecommerce\Entity\LoginWebResponse;
use SoapFault;

class LoginWebTest extends OmniClientSetupTest
{
    public function testSearchUsername()
    {
        $username = $this->getEnvironmentVariableValueGivenName('USERNAME');

        try {
            $params   = [
                'searchType' => Entity\Enum\ContactSearchType::USER_NAME,
                'search'     => $username
            ];
            $response = $this->client->ContactSearch($params);
            $this->assertInstanceOf(ContactSearchResponse::class, $response);
            $this->assertInstanceOf(ArrayOfMemberContact::class, $response->getResult());
            $this->assertGreaterThanOrEqual(1, count($response->getResult()->getMemberContact()));
        } catch (SoapFault $e) {
            // phpcs:ignore Magento2.Security.LanguageConstruct.DirectOutput
            echo $e->getMessage();
        }
    }

    /**
     * @depends testSearchUsername
     */
    public function testLoginUserName()
    {
        $username    = $this->getEnvironmentVariableValueGivenName('USERNAME');
        $password = $this->getEnvironmentVariableValueGivenName('PASSWORD');

        try {
            $params   = [
                'userName' => $username,
                'password' => $password
            ];
            $response = $this->client->LoginWeb($params);
            $this->assertInstanceOf(LoginWebResponse::class, $response);
        } catch (SoapFault $e) {
            // phpcs:ignore Magento2.Security.LanguageConstruct.DirectOutput
            echo $e->getMessage();
        }
    }
}
