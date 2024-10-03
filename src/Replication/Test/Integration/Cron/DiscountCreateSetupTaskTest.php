<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\OfferDiscountLineType;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\ReplDiscMemberType;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\ReplDiscountType;
use \Ls\Replication\Cron\DiscountCreateSetupTask;
use \Ls\Replication\Model\ReplDiscountSearchResults;
use \Ls\Replication\Model\ReplDiscountValidation;
use \Ls\Replication\Test\Integration\AbstractIntegrationTest;
use Magento\CatalogRule\Api\Data\RuleInterface;
use Magento\Framework\Exception\LocalizedException;

class DiscountCreateSetupTaskTest extends AbstractTaskTest
{
    public const SAMPLE_SIMPLE_ITEM_ID = '40015';

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function actualExecute()
    {
        $storeId           = $this->storeManager->getStore()->getId();

        $this->executeUntilReady(DiscountCreateSetupTask::class, [
            LSR::SC_SUCCESS_CRON_DISCOUNT_SETUP
        ]);

        $this->assertCronSuccess(
            [
                LSR::SC_SUCCESS_CRON_DISCOUNT_SETUP,
            ],
            $storeId
        );

        $this->assertOffersWithGroups(AbstractIntegrationTest::SAMPLE_OFFER_CATEGORY_1);
        $this->assertOffersWithGroups(AbstractIntegrationTest::SAMPLE_OFFER_CATEGORY_2);
    }

    public function assertOffersWithGroups($offerNo)
    {
        $scopeId = $this->storeManager->getWebsite()->getId();
        $replDiscounts = $this->getDiscountGivenOfferNo($offerNo, $scopeId);

        if ($replDiscounts) {
            foreach ($replDiscounts as $replDiscount) {
                $replDiscountValidation = $this->getDiscountValidationGivenId(
                    $replDiscount->getValidationPeriodId(),
                    $scopeId
                );

                $catalogRule = $this->getCatalogRuleGivenReplDiscount($replDiscount);
                $this->assertRuleConditions($catalogRule, $replDiscount);
                $this->assertCustomerGroups($catalogRule, $replDiscount);
                $this->assertTrue($catalogRule->getDiscountAmount() == $replDiscount->getDiscountValue());
                $this->assertTrue($catalogRule->getDescription() == $replDiscount->getDescription());

                if ($replDiscount->getIsPercentage()) {
                    $this->assertTrue($catalogRule->getSimpleAction() == 'by_percent');
                } else {
                    $this->assertTrue($catalogRule->getSimpleAction() == 'by_fixed');
                }
                $this->assertDates($replDiscountValidation, $catalogRule);
            }
        }
    }

    public function assertCustomerGroups($catalogRule, $replDiscount)
    {
        $customerGroupsId = $this->getCustomerGroupsIdGivenOffer($replDiscount);
        $ruleCustomerGroupsId = $catalogRule->getCustomerGroupIds();

        $this->assertEqualsCanonicalizing($customerGroupsId, $ruleCustomerGroupsId);
    }

    public function assertRuleConditions($catalogRule, $replDiscount)
    {
        $attributeCode = '';

        if ($replDiscount->getLineType() == OfferDiscountLineType::SPECIAL_GROUP) {
            $attributeCode = LSR::LS_ITEM_SPECIAL_GROUP;
        } elseif ($replDiscount->getLineType() == OfferDiscountLineType::PRODUCT_GROUP) {
            $attributeCode = LSR::LS_ITEM_PRODUCT_GROUP;
        } elseif ($replDiscount->getLineType() == OfferDiscountLineType::ITEM_CATEGORY) {
            $attributeCode = LSR::LS_ITEM_CATEGORY;
        }

        $specialGroupValue = $replDiscount->getNumber();

        $ruleConditions = $catalogRule->getRuleCondition();

        if ($ruleConditions && !empty($attributeCode)) {
            $condition = current($ruleConditions->getConditions());
            $this->assertTrue($condition->getAttribute() == $attributeCode);

            if ($condition->getOperator() == '{}') {
                $this->assertTrue($condition->getValue() == $specialGroupValue.';');
            } else {
                $this->assertTrue($condition->getValue() == $specialGroupValue);
            }
        }
    }

    public function assertDates($replDiscountValidation, $catalogRule)
    {
        $fromDate = $replDiscountValidation->getStartDate();
        $toDate   = $replDiscountValidation->getEndDate();

        if ($fromDate) {
            $this->assertTrue($fromDate == $catalogRule->getFromDate());
        }

        if (strtolower($toDate ?? '') != strtolower('1753-01-01T00:00:00')
            && !empty($toDate)) {
            $this->assertTrue($toDate == $catalogRule->getToDate());
        }
    }

    public function getCatalogRuleGivenReplDiscount($replDiscount)
    {
        $offerName = $replDiscount->getOfferNo() . '-' . $replDiscount->getLineNumber();
        $catalogRule = $this->getRuleByName($offerName);

        return $catalogRule;
    }

    /**
     * Retrieve catalog rule by name from db.
     *
     * @param string $name
     * @return RuleInterface
     * @throws LocalizedException
     */
    public function getRuleByName(string $name): RuleInterface
    {
        $catalogRuleResource = $this->catalogRuleResource->create();
        $select = $catalogRuleResource->getConnection()->select();
        $select->from($catalogRuleResource->getMainTable(), RuleInterface::RULE_ID);
        $select->where(RuleInterface::NAME . ' = ?', $name);
        $ruleId = $catalogRuleResource->getConnection()->fetchOne($select);

        return $this->catalogRuleRepository->get((int)$ruleId);
    }

    public function getDiscountGivenOfferNo($offerNo, $scopeId)
    {
        $filters                = [
            ['field' => 'scope_id', 'value' => $scopeId, 'condition_type' => 'eq'],
            ['field' => 'OfferNo', 'value' => $offerNo, 'condition_type' => 'eq'],
            [
                'field'          => 'Type',
                'value'          => ReplDiscountType::DISC_OFFER,
                'condition_type' => 'eq'
            ]
        ];

        $criteria = $this->replicationHelper->buildCriteriaForDirect($filters, -1, 1);
        /** @var ReplDiscountSearchResults $replDiscounts */
        $replDiscounts = $this->replDiscountRepository->getList($criteria)->getItems();

        return $replDiscounts;
    }

    public function getDiscountValidationGivenId($validationId, $scopeId)
    {
        $filters  = [
            ['field' => 'scope_id', 'value' => $scopeId, 'condition_type' => 'eq'],
            [
                'field'          => 'nav_id',
                'value'          => $validationId,
                'condition_type' => 'eq'
            ]
        ];
        $criteria = $this->replicationHelper->buildCriteriaForDirect($filters, -1);
        /** @var ReplDiscountValidation $replDiscountValidation */
        $replDiscountValidation = current($this->discountValidationRepository->getList($criteria)->getItems());

        return $replDiscountValidation;
    }

    public function getCustomerGroupsIdGivenOffer($item)
    {
        $schemes      = $this->contactHelper->getSchemes();

        if ($item->getLoyaltySchemeCode() == '' ||
            $item->getLoyaltySchemeCode() == null
        ) {
            $useAllGroupIds   = true;
            $customerGroupIds = $this->contactHelper->getAllCustomerGroupIds();
        } else {
            $useAllGroupIds   = false;
            $customerGroupIds = [];
        }

        if (empty($customerGroupIds) && !$useAllGroupIds) {
            if ($item->getMemberType() == ReplDiscMemberType::CLUB
                && !empty($item->getLoyaltySchemeCode()) && !empty($schemes)) {
                $groups = array_keys($schemes, $item->getLoyaltySchemeCode());
                foreach ($groups as $group) {
                    $customerGroupIds[] = $this->contactHelper->
                    getCustomerGroupIdByName($group);
                }
            } else {
                $customerGroupIds[] = $this->contactHelper->getCustomerGroupIdByName(
                    $item->getLoyaltySchemeCode()
                );
            }
        }

        return $customerGroupIds;
    }
}
