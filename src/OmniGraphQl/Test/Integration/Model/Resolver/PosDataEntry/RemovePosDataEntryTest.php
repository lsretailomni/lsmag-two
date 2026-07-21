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
 * removeLsPosDataEntry removes a single applied entry identified by its entry_type + code
 * and recomputes totals, leaving any other applied entries intact. The applied entries are
 * seeded directly on the quote so the removal path is exercised without a second LS Central
 * balance lookup.
 */
class RemovePosDataEntryTest extends GraphQlTestBase
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
     * Removing a gift card entry drops only that entry; the applied voucher stays on the cart.
     *
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('graphql')
    ]
    public function testRemovePosDataEntry()
    {
        $customer  = $this->getOrCreateCustomer();
        $product   = $this->getOrCreateProduct();
        $emptyCart = $this->createCustomerEmptyCart($customer->getId());
        $cart      = $this->addSimpleProduct($emptyCart, $product);

        $cart->setData('ls_pos_data_entries', json_encode([
            [
                'entry_type'  => AbstractIntegrationTest::GIFTCARD_ENTRY_TYPE,
                'entry_no'    => AbstractIntegrationTest::GIFTCARD,
                'pin_code'    => AbstractIntegrationTest::GIFTCARD_PIN,
                'amount'      => (float)AbstractIntegrationTest::GIFTCARD_AMOUNT,
                'tender_type' => '8',
            ],
            [
                'entry_type'  => AbstractIntegrationTest::VOUCHER_ENTRY_TYPE,
                'entry_no'    => AbstractIntegrationTest::VOUCHER,
                'pin_code'    => AbstractIntegrationTest::VOUCHER_PIN,
                'amount'      => (float)AbstractIntegrationTest::VOUCHER_AMOUNT,
                'tender_type' => '7',
            ],
        ]));

        // Collect totals so the quote has a non-zero grand total, matching a real storefront
        // cart. removeLsPosDataEntry recomputes totals via GiftCardManagement::removeEntry
        // (which calls collectTotals), so the cart must start from a properly collected state.
        $cart->setTotalsCollectedFlag(false)->collectTotals();
        $this->cartRepository->save($cart);

        $maskedQuoteId = $this->maskedQuote->execute($cart->getId());
        $headerMap     = ['Authorization' => 'Bearer ' . $this->authToken];

        $response = $this->graphQlMutation(
            $this->getRemoveQuery(
                $maskedQuoteId,
                AbstractIntegrationTest::GIFTCARD_ENTRY_TYPE,
                AbstractIntegrationTest::GIFTCARD
            ),
            [],
            '',
            $headerMap
        );

        $this->assertNotNull($response);
        $entries = $response['removeLsPosDataEntry']['cart']['applied_ls_pos_data_entries'] ?? [];

        $codes = array_column($entries, 'code');
        $this->assertNotContains(AbstractIntegrationTest::GIFTCARD, $codes);
        $this->assertContains(AbstractIntegrationTest::VOUCHER, $codes);

        $savedCart = $this->cartRepository->get($cart->getId());
        $this->assertStringNotContainsString(
            AbstractIntegrationTest::GIFTCARD,
            (string)$savedCart->getLsPosDataEntries()
        );
    }

    /**
     * @param string $maskedQuoteId
     * @param string $entryType
     * @param string $code
     * @return string
     */
    private function getRemoveQuery(string $maskedQuoteId, string $entryType, string $code): string
    {
        return <<<QUERY
                mutation {
                  removeLsPosDataEntry(
                    input:
                      {
                        cart_id: "{$maskedQuoteId}"
                        entry_type: "{$entryType}"
                        code: "{$code}"
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
