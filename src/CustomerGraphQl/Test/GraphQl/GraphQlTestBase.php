<?php
declare(strict_types=1);

namespace Ls\CustomerGraphQl\Test\GraphQl;

use Braintree\Configuration;
use \Ls\Replication\Helper\ReplicationHelper;
use Magento\Framework\Encryption\Encryptor;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Abstract class for graphql test
 */
abstract class GraphQlTestBase extends GraphQlAbstract
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    protected $objectManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = Bootstrap::getObjectManager();
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
        $headers                 = [];
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
        $password = getenv('PASSWORD') ?: throw new \RuntimeException('PASSWORD is not set in .env');

        $loginMutation = <<<MUTATION
        mutation {
            generateCustomerToken(
                email: "{$email}",
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
