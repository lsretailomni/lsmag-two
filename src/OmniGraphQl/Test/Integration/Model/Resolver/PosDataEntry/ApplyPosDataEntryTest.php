<?php

namespace Ls\OmniGraphQl\Test\Integration\Model\Resolver\PosDataEntry;

use \Ls\OmniGraphQl\Test\Integration\GraphQlTestBase;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\OmniGraphQl\Test\Integration\AbstractIntegrationTest;
use Magento\Checkout\Model\Session;
use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Fixture\AppArea;

/**
 * Gift cards (entry_type = GIFTCARDNO) and vouchers (any other tender-type-mapped entry_type)
 * both travel through the unified applyLsPosDataEntry mutation and surface on the cart via
 * the applied_ls_pos_data_entries list.
 */
class ApplyPosDataEntryTest extends GraphQlTestBase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $objectManager;

    /**
     * @var DataFixtureStorageManager
     */
    public $fixtures;

    /**
     * @var string
     */
    private $authToken;

    /**
     * @var QuoteIdToMaskedQuoteIdInterface
     */
    public $maskedQuote;

    /**
     * @var Session
     */
    public $checkoutSession;

    /**
     * @var ManagerInterface
     */
    public $eventManager;

    /**
     * @var BasketHelper
     */
    public $basketHelper;

    /**
     * @var CartRepositoryInterface
     */
    public $cartRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->authToken       = $this->loginAndFetchToken();
        $this->objectManager   = Bootstrap::getObjectManager();
        $this->fixtures        = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->maskedQuote     = $this->objectManager->get(QuoteIdToMaskedQuoteIdInterface::class);
        $this->checkoutSession = $this->objectManager->create(Session::class);
        $this->eventManager    = $this->objectManager->create(ManagerInterface::class);
        $this->basketHelper    = $this->objectManager->create(BasketHelper::class);
        $this->cartRepository  = $this->objectManager->get(CartRepositoryInterface::class);
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('graphql')
    ]
    public function testApplyGiftCard()
    {
        $entries = $this->applyEntry(
            AbstractIntegrationTest::GIFTCARD_ENTRY_TYPE,
            AbstractIntegrationTest::GIFTCARD,
            AbstractIntegrationTest::GIFTCARD_PIN,
            AbstractIntegrationTest::GIFTCARD_AMOUNT
        );

        $codes = array_column($entries, 'code');
        $this->assertContains(AbstractIntegrationTest::GIFTCARD, $codes);

        foreach ($entries as $entry) {
            if ($entry['code'] === AbstractIntegrationTest::GIFTCARD) {
                $this->assertSame('GIFTCARDNO', strtoupper((string)$entry['entry_type']));
                $this->assertEquals(
                    (float)AbstractIntegrationTest::GIFTCARD_AMOUNT,
                    (float)$entry['amount']
                );
            }
        }
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('graphql')
    ]
    public function testApplyVoucher()
    {
        $entries = $this->applyEntry(
            AbstractIntegrationTest::VOUCHER_ENTRY_TYPE,
            AbstractIntegrationTest::VOUCHER,
            AbstractIntegrationTest::VOUCHER_PIN,
            AbstractIntegrationTest::VOUCHER_AMOUNT
        );

        $codes = array_column($entries, 'code');
        $this->assertContains(AbstractIntegrationTest::VOUCHER, $codes);

        foreach ($entries as $entry) {
            if ($entry['code'] === AbstractIntegrationTest::VOUCHER) {
                $this->assertNotSame('GIFTCARDNO', strtoupper((string)$entry['entry_type']));
            }
        }
    }

    /**
     * Build a cart, apply a POS data entry through GraphQL and return the applied entries list.
     *
     * @param string $entryType
     * @param string $code
     * @param string $pin
     * @param string $amount
     * @return array
     */
    private function applyEntry(string $entryType, string $code, string $pin, string $amount): array
    {
        $customer      = $this->getOrCreateCustomer();
        $product       = $this->getOrCreateProduct();
        $emptyCart     = $this->createCustomerEmptyCart($customer->getId());
        $cart          = $this->addSimpleProduct($emptyCart, $product);

        // api-functional tests run against the live store without DB rollback, and
        // createEmptyCartForCustomer reuses the customer's existing active cart, so a prior
        // run may have left POS data entries applied. Reset them for a deterministic start
        // (otherwise the apply is rejected with "This entry is already applied").
        $cart->setData('ls_pos_data_entries', null);

        // Collect totals so the quote has a non-zero grand total. The stateless GraphQL
        // resolver has no checkout session, so GiftCardManagement::applyEntry derives the
        // order balance from the quote's grand total (Data::getOrderBalance); without a
        // collected total the applied amount would be rejected as exceeding a $0.00 balance.
        $cart->setTotalsCollectedFlag(false)->collectTotals();
        $this->cartRepository->save($cart);

        $maskedQuoteId = $this->maskedQuote->execute($cart->getId());
        $headerMap     = ['Authorization' => 'Bearer ' . $this->authToken];

        $response = $this->graphQlMutation(
            $this->getApplyQuery($maskedQuoteId, $entryType, $code, $pin, $amount),
            [],
            '',
            $headerMap
        );

        $this->assertNotNull($response);
        $this->assertArrayHasKey('applyLsPosDataEntry', $response);
        $this->assertArrayHasKey('applied_ls_pos_data_entries', $response['applyLsPosDataEntry']['cart']);

        return $response['applyLsPosDataEntry']['cart']['applied_ls_pos_data_entries'] ?? [];
    }

    /**
     * @param string $maskedQuoteId
     * @param string $entryType
     * @param string $code
     * @param string $pin
     * @param string $amount
     * @return string
     */
    private function getApplyQuery(
        string $maskedQuoteId,
        string $entryType,
        string $code,
        string $pin,
        string $amount
    ): string {
        $amount = (float)$amount;

        return <<<QUERY
                mutation {
                  applyLsPosDataEntry(
                    input:
                      {
                        cart_id: "{$maskedQuoteId}"
                        entry_type: "{$entryType}"
                        code: "{$code}"
                        pin: "{$pin}"
                        amount: {$amount}
                      }
                    ) {
                        cart {
                            applied_ls_pos_data_entries {
                                entry_type
                                code
                                amount
                                pin
                            }
                            prices {
                                grand_total {
                                    value
                                }
                            }
                        }
                    }
                }
            QUERY;
    }
}
