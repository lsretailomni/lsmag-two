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

define('PASSWORD', getenv('PASSWORD'));
define('EMAIL', getenv('EMAIL'));
define('FIRST_NAME', getenv('FIRST_NAME'));
define('LAST_NAME', getenv('LAST_NAME'));
define('CUSTOMER_ID', getenv('CUSTOMER_ID'));
define('CS_URL', getenv('CS_URL'));
define('CS_VERSION', getenv('CS_VERSION'));
define('LS_VERSION', getenv('LS_VERSION'));
define('CS_STORE', getenv('CS_STORE'));
define('ENABLED', getenv('ENABLED'));
define('USERNAME', getenv('USERNAME_1'));
define('LSR_ID', getenv('LSR_ID'));
define('LSR_CARD_ID', getenv('LSR_CARD_ID'));
class AbstractIntegrationTest extends TestCase
{
    //php const need to defined in phpunit.xml file
    public const PASSWORD = PASSWORD;
    public const EMAIL = EMAIL;
    public const FIRST_NAME = FIRST_NAME;
    public const LAST_NAME = LAST_NAME;
    public const CUSTOMER_ID = CUSTOMER_ID;
    public const CS_URL = CS_URL;
    public const CS_VERSION = CS_VERSION;
    public const LS_VERSION = LS_VERSION;
    public const CS_STORE = CS_STORE;
    public const ENABLED =  ENABLED;
    public const USERNAME = USERNAME;
    public const LSR_ID = LSR_ID;
    public const LSR_CARD_ID = LSR_CARD_ID;

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
