<?php
declare(strict_types=1);

namespace Ls\Customer\Test\Integration;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerRegistry;
use Magento\JwtUserToken\Api\Data\Revoked;
use Magento\JwtUserToken\Api\RevokedRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

if (!defined("PASSWORD")) { define('PASSWORD', getenv('PASSWORD')); }
if (!defined("EMAIL")) { define('EMAIL', getenv('EMAIL')); }
if (!defined("FIRST_NAME")) { define('FIRST_NAME', getenv('FIRST_NAME')); }
if (!defined("LAST_NAME")) { define('LAST_NAME', getenv('LAST_NAME')); }
if (!defined("CUSTOMER_ID")) { define('CUSTOMER_ID', getenv('CUSTOMER_ID'));}
if (!defined("CS_URL")) { define('CS_URL', getenv('CS_URL')); }
if (!defined("BASE_URL")) { define('BASE_URL', getenv('BASE_URL')); }
if (!defined("SC_COMPANY_NAME")) { define('SC_COMPANY_NAME', getenv('SC_COMPANY_NAME')); }
if (!defined("SC_TENANT")) { define('SC_TENANT', getenv('SC_TENANT')); }
if (!defined("SC_CLIENT_ID")) { define('SC_CLIENT_ID', getenv('SC_CLIENT_ID')); }
if (!defined("SC_CLIENT_SECRET")) { define('SC_CLIENT_SECRET', getenv('SC_CLIENT_SECRET')); }
if (!defined("SC_ENVIRONMENT_NAME")) { define('SC_ENVIRONMENT_NAME', getenv('SC_ENVIRONMENT_NAME')); }
if (!defined("CS_VERSION")) { define('CS_VERSION', getenv('CS_VERSION')); }
if (!defined("LS_VERSION")) { define('LS_VERSION', getenv('LS_VERSION')); }
if (!defined("CS_STORE")) { define('CS_STORE', getenv('CS_STORE')); }
if (!defined("ENABLED")) { define('ENABLED', getenv('ENABLED')); }
if (!defined("USERNAME")) { define('USERNAME', getenv('USERNAME')); }
if (!defined("LSR_ID")) { define('LSR_ID', getenv('LSR_ID')); }
if (!defined("LSR_CARD_ID")) { define('LSR_CARD_ID', getenv('LSR_CARD_ID')); }
if (!defined("ACCOUNT_ID")) { define('ACCOUNT_ID', getenv('ACCOUNT_ID')); }
class AbstractIntegrationTest extends TestCase
{
    //php const need to defined in phpunit.xml file
    public const PASSWORD = PASSWORD;
    public const EMAIL = EMAIL;
    public const FIRST_NAME = FIRST_NAME;
    public const LAST_NAME = LAST_NAME;
    public const CUSTOMER_ID = CUSTOMER_ID;
    public const CS_URL = CS_URL;
    public const BASE_URL = BASE_URL;
    public const SC_COMPANY_NAME = SC_COMPANY_NAME;
    public const SC_TENANT = SC_TENANT;
    public const SC_CLIENT_ID = SC_CLIENT_ID;
    public const SC_CLIENT_SECRET = SC_CLIENT_SECRET;
    public const SC_ENVIRONMENT_NAME = SC_ENVIRONMENT_NAME;
    public const CS_VERSION = CS_VERSION;
    public const LS_VERSION = LS_VERSION;
    public const CS_STORE = CS_STORE;
    public const ENABLED =  ENABLED;
    public const USERNAME = USERNAME;
    public const LSR_ID = LSR_ID;
    public const LSR_CARD_ID = LSR_CARD_ID;
    public const ACCOUNT_ID = ACCOUNT_ID;

    public static function createCustomerWithCustomAttributesFixture()
    {
        $objectManager = Bootstrap::getObjectManager();
        $customer = $objectManager->create(Customer::class);
        /** @var CustomerRegistry $customerRegistry */
        $customerRegistry = $objectManager->get(CustomerRegistry::class);
        /** @var Customer $customer */
        $customer->setWebsiteId(1)
            ->setId(self::CUSTOMER_ID)
            ->setEmail(self::EMAIL)
            ->setPassword(self::PASSWORD)
            ->setGroupId(1)
            ->setStoreId(1)
            ->setIsActive(1)
            ->setPrefix('Mr.')
            ->setFirstname('John')
            ->setMiddlename('A')
            ->setLastname('Smith')
            ->setSuffix('Esq.')
            ->setDefaultBilling(1)
            ->setDefaultShipping(1)
            ->setTaxvat('12')
            ->setGender(1)
            ->setData('lsr_username', self::USERNAME)
            ->setData('lsr_id', self::LSR_ID)
            ->setData('lsr_cardid', self::LSR_CARD_ID);

        $customer->isObjectNew(true);
        $customer->save();
        $customerRegistry->remove($customer->getId());
        /** @var RevokedRepositoryInterface $revokedRepo */
        $revokedRepo = $objectManager->get(RevokedRepositoryInterface::class);
        $revokedRepo->saveRevoked(
            new Revoked(
                UserContextInterface::USER_TYPE_CUSTOMER,
                (int) $customer->getId(),
                time() - 3600 * 24
            )
        );
    }

    /**
     * Get environment variable value given name
     *
     * @param $name
     * @return array|false|string
     */
    public function getEnvironmentVariableValueGivenName($name)
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        return getenv($name);
    }

    public function testExecute()
    {
        $this->assertEquals(1, 1);
    }
}
