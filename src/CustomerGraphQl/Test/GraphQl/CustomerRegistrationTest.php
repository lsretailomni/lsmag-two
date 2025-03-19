<?php
declare(strict_types=1);

namespace Ls\CustomerGraphQl\Test\GraphQl;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * GraphQl Test for Customer Registration, Login, and Deletion
 */
class CustomerRegistrationTest extends GraphQlTestBase
{
    /** @var $customerRepository */
    private $customerRepository;

    /** @var $customerEmail */
    private $customerEmail;

    /** @var $customerPassword */
    private $customerPassword;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customerRepository = Bootstrap::getObjectManager()->get(CustomerRepositoryInterface::class);

        // Use environment variables or fallback to hardcoded values
        $this->customerEmail    = 'test.customer' . rand(10, 100000) . '@example.com';
        $this->customerPassword = 'Password123';
    }

    /** Test customer graphql registration and login
     *
     * @return void
     */
    public function testCustomerRegistrationAndLogin(): void
    {
        // Step 1: Register customer via GraphQL mutation
        $registerQuery = <<<MUTATIONCREATE
        mutation {
            createCustomer(
                input: {
                    firstname: "Test"
                    lastname: "Customer"
                    email: "{$this->customerEmail}"
                    password: "{$this->customerPassword}"
                }
            ) {
                customer {
                    firstname
                    lastname
                    email
                }
            }
        }
        MUTATIONCREATE;

        $registerResponse = $this->executeMutation($registerQuery);
        $this->assertArrayHasKey('createCustomer', $registerResponse);
        $this->assertArrayHasKey('customer', $registerResponse['createCustomer']);
        $this->assertEquals($this->customerEmail, $registerResponse['createCustomer']['customer']['email']);

        // Step 2: Log in with registered customer credentials
        $loginQuery = <<<MUTATION
        mutation {
            generateCustomerToken(
                email: "{$this->customerEmail}"
                password: "{$this->customerPassword}"
            ) {
                token
            }
        }
        MUTATION;

        $loginResponse = $this->executeMutation($loginQuery);
        $this->assertArrayHasKey('generateCustomerToken', $loginResponse);
        $this->assertArrayHasKey('token', $loginResponse['generateCustomerToken']);
        $this->assertNotEmpty($loginResponse['generateCustomerToken']['token'], 'Login token should not be empty.');
    }
}