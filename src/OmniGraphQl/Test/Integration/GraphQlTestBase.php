<?php
declare(strict_types=1);

namespace Ls\OmniGraphQl\Test\Integration;

use Braintree\Configuration;
use \Ls\Core\Model\LSR;
use \Ls\Replication\Helper\ReplicationHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Exception\LocalizedException;

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

    /**
     * @var SourceItemsSaveInterface
     */
    protected $sourceItemsSaveInterface;

    /**
     * @var SourceItemInterfaceFactory
     */
    protected $sourceItem;

    /**
     * @var IndexerRegistry
     */
    protected $indexerFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager            = Bootstrap::getObjectManager();
        $this->sourceItemsSaveInterface = $this->objectManager->create(SourceItemsSaveInterface::class);
        $this->sourceItem               = $this->objectManager->create(SourceItemInterfaceFactory::class);
        $this->indexerFactory           = $this->objectManager->get(IndexerRegistry::class);

        $this->productSku = AbstractIntegrationTest::ITEM_SIMPLE;
        $this->email      = AbstractIntegrationTest::EMAIL;
        $this->password   = AbstractIntegrationTest::PASSWORD;

        $braintreePrivateKey = $this->getEnvironment('BRAINTREE_PRIVATE_KEY');
        $braintreePublicKey  = $this->getEnvironment('BRAINTREE_PUBLIC_KEY');
        $braintreeMerchantId = $this->getEnvironment('BRAINTREE_MERCHANT_ID');
        $centralType = $this->getEnvironment('SC_REPLICATION_CENTRAL_TYPE');
        $debug = $this->getEnvironment('ENABLED');
        $baseUrl = $this->getEnvironment('BASE_URL');
        $webServiceUri = $this->getEnvironment('SC_WEB_SERVICE_URI');
        $webServiceOdataUri = $this->getEnvironment('SC_ODATA_URI');
        $webServiceUsername = $this->getEnvironment('SC_USERNAME');
        $webServicePassword = $this->getEnvironment('SC_PASSWORD');
        $webStore = $this->getEnvironment('WEB_STORE');
        $environmentName = $this->getEnvironment('SC_ENVIRONMENT_NAME');
        $companyName = $this->getEnvironment('SC_COMPANY_NAME');
        $tenant = $this->getEnvironment('SC_TENANT');
        $clientId = $this->getEnvironment('SC_CLIENT_ID');
        $clientSecret = $this->getEnvironment('SC_CLIENT_SECRET');
        $industry = $this->getEnvironment('SC_INDUSTRY');

        $industryConfig = 'ls_mag/ls_industry/ls_choose_industry';
        $debugConfig = 'ls_mag/service/debug';
        $centralTypeConfig = 'ls_mag/service/central_type';
        $baseUrlConfig = 'ls_mag/service/base_url';
        $webServiceUriConfig = 'ls_mag/service/web_service_uri';
        $webServiceOdataUriConfig = 'ls_mag/service/odata_service_uri';
        $webServiceUsernameConfig = 'ls_mag/service/username';
        $webServicePasswordConfig = 'ls_mag/service/password';
        $webStoreConfig = 'ls_mag/service/selected_store';
        $environmentNameConfig = 'ls_mag/service/environment_name';
        $companyNameConfig = 'ls_mag/service/company_name';
        $tenantConfig = 'ls_mag/service/tenant';
        $clientIdConfig = 'ls_mag/service/client_id';
        $clientSecretConfig = 'ls_mag/service/client_secret';
        $this->saveConfig($industry, $industryConfig);
        $this->saveConfig($debug, $debugConfig);
        $this->saveConfig($centralType, $centralTypeConfig);
        $this->saveConfig($baseUrl, $baseUrlConfig);
        $this->saveConfig($webServiceUri, $webServiceUriConfig);
        $this->saveConfig($webServiceOdataUri, $webServiceOdataUriConfig);
        $this->saveConfig($webServiceUsername, $webServiceUsernameConfig);
        $this->saveConfig($webServicePassword, $webServicePasswordConfig, true);
        $this->saveConfig($webStore, $webStoreConfig);
        $this->saveConfig($environmentName, $environmentNameConfig);
        $this->saveConfig($companyName, $companyNameConfig);
        $this->saveConfig($tenant, $tenantConfig);
        $this->saveConfig($clientId, $clientIdConfig);
        $this->saveConfig($clientSecret, $clientSecretConfig, true);

        $privateKeyPath = 'payment/braintree/sandbox_private_key';
        $publicKeyPath = 'payment/braintree/sandbox_public_key';
        $merchantIdPath = 'payment/braintree/sandbox_merchant_id';
        $debug = 'payment/braintree/debug';
        $sendLies = 'payment/braintree/send_line_items';
        Configuration::environment('sandbox');
        Configuration::merchantId($braintreeMerchantId);
        Configuration::publicKey($braintreePublicKey);
        Configuration::privateKey($braintreePrivateKey);
        Configuration::gateway()->plan()->all();
        $this->saveConfig($braintreePublicKey, $publicKeyPath, true);
        $this->saveConfig($braintreePrivateKey, $privateKeyPath, true);
        $this->saveConfig($braintreeMerchantId, $merchantIdPath);
        $this->saveConfig('1', $debug);
        $this->saveConfig('0', $sendLies);

        $replicationHelper = $this->objectManager->get(ReplicationHelper::class);
        $replicationHelper->flushByTypeCode('config');
    }

    /**
     * Save configuration value
     *
     * @param mixed $value
     * @param string $path
     * @param bool $isSensitive
     * @return void
     */
    public function saveConfig($value, $path, $isSensitive = false)
    {
        if ($value === false || $value === null) {
            return;
        }
        $replicationHelper = $this->objectManager->get(ReplicationHelper::class);

        if ($isSensitive) {
            $encryptor = $this->objectManager->get(Encryptor::class);
            $value     = $encryptor->encrypt($value);
        }
        $replicationHelper->updateConfigValue($value, $path, 1);
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
        $headers                 = [];
        $headers['store']        = "default";
        $headers['content-type'] = "application/json";

        // Add Authorization header if token is provided
        if ($token !== "") {
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
        if ($token !== "") {
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
        $email    = getenv('EMAIL') ?: throw new \RuntimeException('EMAIL is not set in .env');
        $password = getenv('PASSWORD') ?:
            throw new \RuntimeException('PASSWORD is not set in .env');

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
            $product = $this->objectManager->get(ProductRepositoryInterface::class)->get($this->productSku);
            return $product;
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
            if (!$customer->getId()) {
                throw NoSuchEntityException::singleField('email', $this->email);
            }
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
            ->setWebsiteIds([1])
            ->setTypeId('simple')
            ->setStockData(
                [
                    'qty'                     => 10000,
                    'is_in_stock'             => 1,
                    'use_config_manage_stock' => 1,
                    'manage_stock'            => 1
                ]
            )
            ->setCustomAttribute('unit_of_measure', 'PCS')
            ->setCustomAttribute(LSR::LS_ITEM_ID_ATTRIBUTE_CODE, $this->productSku)
            ->save();

        try {
            $sourceItems = [];
            $sourceItem  = $this->sourceItem->create();
            $sourceItem->setSourceCode('default');
            $sourceItem->setQuantity(10000);
            $sourceItem->setSku($this->productSku);
            $sourceItem->setStatus(1);
            $sourceItems[] = $sourceItem;

            $this->sourceItemsSaveInterface->execute($sourceItems);

            $indexer = $this->indexerFactory->get('cataloginventory_stock');
            $indexer->reindexAll();
        } catch (NoSuchEntityException $e) {
            $this->fail("Product with SKU $this->productSku does not exist.");
        } catch (LocalizedException $e) {
            $this->fail("Error: " . $e->getMessage());
        }

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
            ->setPassword($this->password)
            ->setLsrUsername(AbstractIntegrationTest::USERNAME)
            ->setLsrId(AbstractIntegrationTest::LSR_ID)
            ->setLsrCardid(AbstractIntegrationTest::LSR_CARD_ID)
            ->setLsrToken(AbstractIntegrationTest::CUSTOMER_ID)
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

    /**
     * return environment variable
     *
     * @param $param
     * @return array|false|string
     */
    public function getEnvironment($param)
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        return getenv($param);
    }
}
