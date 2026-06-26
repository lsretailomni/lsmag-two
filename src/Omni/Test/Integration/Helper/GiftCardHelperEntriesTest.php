<?php
declare(strict_types=1);

namespace Ls\Omni\Test\Integration\Helper;

use Ls\Omni\Client\CentralEcommerce\Entity\POSDataEntry;
use Ls\Omni\Helper\GiftCardHelper;
use Ls\Omni\Test\Integration\AbstractIntegrationTest;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Integration tests for GiftCardHelper::isEntryApplicable() and the
 * JSON entry helper methods introduced for multiple POS data entry support.
 *
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 */
class GiftCardHelperEntriesTest extends AbstractIntegrationTest
{
    public $objectManager;
    public $helper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = Bootstrap::getObjectManager();
        $this->helper        = $this->objectManager->get(GiftCardHelper::class);
    }

    private function makeEntry(array $data): POSDataEntry
    {
        $entry = $this->objectManager->create(POSDataEntry::class);
        foreach ($data as $key => $value) {
            $entry->setData($key, $value);
        }
        return $entry;
    }

    // -------------------------------------------------------------------------
    // isEntryApplicable — BlockedOnECom
    // -------------------------------------------------------------------------

    /**
     * @magentoAppIsolation enabled
     */
    public function testIsEntryApplicableReturnsTrueForCleanEntry(): void
    {
        $entry = $this->makeEntry([]);
        $this->assertTrue($this->helper->isEntryApplicable($entry));
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testIsEntryApplicableReturnsFalseWhenBlockedOnEComIsTrue(): void
    {
        $entry = $this->makeEntry([POSDataEntry::BLOCKED_ON_ECOM => 'true']);
        $this->assertFalse($this->helper->isEntryApplicable($entry));
    }

    /**
     * SOAP serialises booleans as strings; "false" must not block the entry.
     *
     * @magentoAppIsolation enabled
     */
    public function testIsEntryApplicableReturnsTrueWhenBlockedOnEComIsFalseString(): void
    {
        $entry = $this->makeEntry([POSDataEntry::BLOCKED_ON_ECOM => 'false']);
        $this->assertTrue($this->helper->isEntryApplicable($entry));
    }

    // -------------------------------------------------------------------------
    // isEntryApplicable — Unposted
    // -------------------------------------------------------------------------

    /**
     * @magentoAppIsolation enabled
     */
    public function testIsEntryApplicableReturnsFalseWhenUnpostedIsTrue(): void
    {
        $entry = $this->makeEntry([POSDataEntry::UNPOSTED => true]);
        $this->assertFalse($this->helper->isEntryApplicable($entry));
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testIsEntryApplicableReturnsTrueWhenUnpostedIsFalse(): void
    {
        $entry = $this->makeEntry([POSDataEntry::UNPOSTED => false]);
        $this->assertTrue($this->helper->isEntryApplicable($entry));
    }

    // -------------------------------------------------------------------------
    // isEntryApplicable — WebStore
    // -------------------------------------------------------------------------

    /**
     * Empty WebStore means "any store" — no check needed.
     *
     * @magentoAppIsolation enabled
     */
    public function testIsEntryApplicableReturnsTrueWhenWebStoreIsEmpty(): void
    {
        $entry = $this->makeEntry([POSDataEntry::WEB_STORE => '']);
        $this->assertTrue($this->helper->isEntryApplicable($entry));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture current_store ls_mag/service/selected_store S001
     */
    public function testIsEntryApplicableReturnsFalseWhenWebStoreMismatch(): void
    {
        $entry = $this->makeEntry([POSDataEntry::WEB_STORE => 'OTHER']);
        $this->assertFalse($this->helper->isEntryApplicable($entry));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture current_store ls_mag/service/selected_store S001
     */
    public function testIsEntryApplicableReturnsTrueWhenWebStoreMatches(): void
    {
        $entry = $this->makeEntry([POSDataEntry::WEB_STORE => 'S001']);
        $this->assertTrue($this->helper->isEntryApplicable($entry));
    }

    // -------------------------------------------------------------------------
    // JSON entry helpers
    // -------------------------------------------------------------------------

    private function mixedEntriesJson(): string
    {
        return json_encode([
            ['entry_type' => 'GIFTCARDNO',    'entry_no' => 'GC001', 'amount' => 30.0],
            ['entry_type' => 'STOREVOUCHER',  'entry_no' => 'SV001', 'amount' => 15.0],
            ['entry_type' => 'GIFTCARDNO',    'entry_no' => 'GC002', 'amount' => 20.0],
            ['entry_type' => 'INCOMEACCOUNT', 'entry_no' => 'IA001', 'amount' => 10.0],
        ]);
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGetGiftCardEntriesReturnsOnlyGiftCardEntries(): void
    {
        $result = $this->helper->getGiftCardEntries($this->mixedEntriesJson());

        $this->assertCount(2, $result);
        foreach ($result as $entry) {
            $this->assertSame('GIFTCARDNO', strtoupper($entry['entry_type']));
        }
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGetVoucherEntriesReturnsOnlyNonGiftCardEntries(): void
    {
        $result = $this->helper->getVoucherEntries($this->mixedEntriesJson());

        $this->assertCount(2, $result);
        foreach ($result as $entry) {
            $this->assertNotSame('GIFTCARDNO', strtoupper($entry['entry_type']));
        }
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGetTotalFromEntriesSumsAllAmounts(): void
    {
        $total = $this->helper->getTotalFromEntries($this->mixedEntriesJson());
        $this->assertEquals(75.0, $total);
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGetTotalFromEntriesReturnsZeroForNullInput(): void
    {
        $this->assertEquals(0.0, $this->helper->getTotalFromEntries(null));
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testIsGiftCardAlreadyAppliedInEntriesFindsExistingEntry(): void
    {
        $this->assertTrue(
            $this->helper->isGiftCardAlreadyAppliedInEntries($this->mixedEntriesJson(), 'GC001')
        );
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testIsGiftCardAlreadyAppliedInEntriesReturnsFalseForMissing(): void
    {
        $this->assertFalse(
            $this->helper->isGiftCardAlreadyAppliedInEntries($this->mixedEntriesJson(), 'GC999')
        );
    }

    /**
     * Voucher lookup must not match GIFTCARDNO entries with the same code.
     *
     * @magentoAppIsolation enabled
     */
    public function testIsVoucherAlreadyAppliedFindsVoucherEntry(): void
    {
        $this->assertTrue(
            $this->helper->isVoucherAlreadyApplied($this->mixedEntriesJson(), 'SV001')
        );
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testIsVoucherAlreadyAppliedReturnsFalseForGiftCardCode(): void
    {
        // GC001 exists but is a GIFTCARDNO entry — getVoucherEntries excludes it
        $this->assertFalse(
            $this->helper->isVoucherAlreadyApplied($this->mixedEntriesJson(), 'GC001')
        );
    }
}
