<?php
declare(strict_types=1);

namespace Ls\OmniGraphQl\Test\Integration;

use \Ls\Core\Model\LSR;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Abstract class for graphql test
 */
abstract class GraphQlTestBase extends GraphQlAbstract
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    protected $objectManager;

    /** @var string */
    protected $productSku;

    /** @var string */
    protected $email;

    /** @var string */
    protected $password;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->productSku    = (defined('WEB_API_TEST_PRODUCT_SKU')) ?
            WEB_API_TEST_PRODUCT_SKU : AbstractIntegrationTest::ITEM_SIMPLE;
        $this->email         = (defined('WEB_API_TEST_EMAIL')) ?
            WEB_API_TEST_EMAIL : 'pipeline_retail@lsretail.com';
        $this->password      = (defined('PASSWORD')) ?
            PASSWORD : 'Nmswer123@';
    }

    /**
     * Executes a GraphQL query with optional authorization headers.
     *
     * @param string $query The GraphQL query or mutation.
     * @param ?string $token Optional authentication token for Bearer authorization.
     *
     * @return array<string, mixed> The response array from the GraphQL resolver.
     * @throws \RuntimeException If the response is not valid or has errors.
     * @throws \Exception
     */
    protected function executeQuery(string $query, ?string $token = ''): array
    {
        // Initialize headers array
        $headers = [];

        // Add Authorization header if token is provided
        if ($token !== null) {
            $headers['Authorization'] = "Bearer $token";
        }

        return $this->graphQlQuery($query, [], '', $headers);
    }

    /**
     * Executes a GraphQL mutation with optional authorization headers.
     *
     * @param string $query The GraphQL query or mutation.
     * @param ?string $token Optional authentication token for Bearer authorization.
     *
     * @return array<string, mixed> The response array from the GraphQL resolver.
     * @throws \RuntimeException If the response is not valid or has errors.
     * @throws \Exception
     */
    protected function executeMutation(string $query, array $variables = [], ?string $token = ''): array
    {
        // Initialize headers array
        $headers = [];

        // Add Authorization header if token is provided
        if ($token !== null) {
            $headers['Authorization'] = "Bearer $token";
        }

        return $this->graphQlMutation($query, $variables, '', $headers);
    }

    /**
     * Authenticates the user and retrieves the token.
     *
     * @return string The authentication token.
     * @throws \Exception
     */
    protected function loginAndFetchToken(): string
    {
        $email    = getenv('WEB_API_TEST_EMAIL') ?: throw new \RuntimeException('TEST_USER_EMAIL is not set in .env');
        $password = getenv('PASSWORD') ?:
            throw new \RuntimeException('TEST_USER_PASSWORD is not set in .env');

        $loginMutation = <<<MUTATION
        mutation {
            generateCustomerToken(
                email: "{$email}"
                password: "{$password}"
            ) {
                token
            }
        }
        MUTATION;

        $response = $this->executeMutation($loginMutation);

        if (empty($response['generateCustomerToken']['token'])) {
            throw new \RuntimeException('Failed to retrieve authentication token.');
        }

        return $response['generateCustomerToken']['token'];
    }

    /**
     * Get or create a product by SKU.
     *
     * @return \Magento\Catalog\Model\Product
     * @throws \Exception
     */
    protected function getOrCreateProduct()
    {
        try {
            return $this->objectManager->get(ProductRepositoryInterface::class)->get($this->productSku);
        } catch (NoSuchEntityException $e) {
            return $this->createProduct();
        }
    }

    /**
     * Get or create a customer by email.
     *
     * @return Customer
     */
    protected function getOrCreateCustomer()
    {
        try {
            $customer = $this->objectManager->get(\Magento\Customer\Model\CustomerFactory::class)->create();
            $customer->setWebsiteId(1)
                ->loadByEmail($this->email);
            $customerSession = $this->objectManager->get(\Magento\Customer\Model\Session::class);
            $customerSession->setCustomer($customer);

            $customerSession->setData(LSR::SESSION_CUSTOMER_SECURITYTOKEN, $customer->getData('lsr_token'));
            $customerSession->setData(LSR::SESSION_CUSTOMER_LSRID, $customer->getData('lsr_id'));
            $customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getData('lsr_cardid'));

            return $customer;
        } catch (NoSuchEntityException $e) {
            return $this->createCustomer();
        }
    }

    /**
     * Create a simple product
     *
     * @return \Magento\Catalog\Model\Product
     * @throws \Exception
     */
    private function createProduct()
    {
        /** @var ProductFactory $productFactory */
        $productFactory = $this->objectManager->get(ProductFactory::class);
        $product        = $productFactory->create();
        $product->setSku($this->productSku)
            ->setName('Test Product')
            ->setPrice(100)
            ->setAttributeSetId(4) // Default attribute set
            ->setStatus(1) // Enabled
            ->setVisibility(4) // Catalog, Search
            ->setTypeId('simple')
            ->setStockData(['qty' => 10000, 'is_in_stock' => 1])
            ->setCustomAttribute('unit_of_measure', 'PCS')
            ->setCustomAttribute(LSR::LS_ITEM_ID_ATTRIBUTE_CODE, $this->productSku)
            ->save();

        return $product;
    }

    /**
     * Create Customer
     *
     * @return \Magento\Customer\Model\Customer
     */
    private function createCustomer()
    {
        /** @var CustomerFactory $customerFactory */
        $customerFactory = $this->objectManager->get(CustomerFactory::class);
        $customer        = $customerFactory->create();
        $customer->setWebsiteId(1)
            ->setEmail($this->email)
            ->setFirstname('John')
            ->setLastname('Doe')
            ->setPassword('password')
            ->save();

        return $this->objectManager->get(CustomerRepositoryInterface::class)->get($this->email);
    }

    /**
     * Create customer empty cart
     *
     * @param $customerId
     * @return mixed
     */
    protected function createCustomerEmptyCart($customerId)
    {
        $cartManagement     = $this->objectManager->get(CartManagementInterface::class);
        $cartRepository     = $this->objectManager->get(CartRepositoryInterface::class);
        $quoteIdMaskFactory = $this->objectManager->get(QuoteIdMaskFactory::class);
        $cartId             = $cartManagement->createEmptyCartForCustomer($customerId);
        $cart               = $cartRepository->get($cartId);
        $reservedOrderId    = 'ITGQL-' . rand(10000001, 99999999);
        $cart->setReservedOrderId($reservedOrderId);
        $cartRepository->save($cart);

        /** @var QuoteIdMask $quoteIdMask */
        $quoteIdMask = $quoteIdMaskFactory->create();
        $quoteIdMask->setQuoteId($cartId)
            ->save();

        return $cart;
    }

    /**
     * Add simple product to cart
     *
     * @param $cart
     * @param $product
     * @return mixed
     */
    protected function addSimpleProduct($cart, $product)
    {
        try {
            $cartRepository = $this->objectManager->get(CartRepositoryInterface::class);
            $cart->addProduct($product, 1);
            $cartRepository->save($cart);
            return $cart;
        } catch (NoSuchEntityException $e) {
            $this->fail("Error:- " . $e->getMessage());
        }
    }
}
