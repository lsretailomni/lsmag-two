<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\DiscountValueType;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\OfferDiscountLineType;
use \Ls\Replication\Cron\DiscountCreateSetupTask;
use \Ls\Replication\Model\ReplDiscountSearchResults;
use \Ls\Replication\Model\ReplDiscountValidation;
use \Ls\Replication\Test\Integration\AbstractIntegrationTest;
use \Ls\Replication\Cron\ProductCreateTask;
use Magento\CatalogRule\Api\Data\RuleInterface;
use Magento\Store\Model\ScopeInterface;

class DiscountCreateSetupTaskTest extends AbstractTaskTest
{
    public $discountCron;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->discountCron = $this->objectManager->get(DiscountCreateSetupTask::class);
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
        $storeId                   = $this->storeManager->getStore()->getId();
        $this->discountCron->store = $this->storeManager->getStore();
        $this->updateAllRelevantItemRecords(
            1,
            [
                AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
                AbstractIntegrationTest::SAMPLE_CONFIGURABLE_UOM_ITEM_ID,
                AbstractIntegrationTest::SAMPLE_CONFIGURABLE_VARIANT_ITEM_ID,
                AbstractIntegrationTest::SAMPLE_DISCOUNTED_CONFIGURABLE_ITEM_ID,
                AbstractIntegrationTest::SAMPLE_CONFIGURABLE2_VARIANT_ITEM_ID,
                AbstractIntegrationTest::SAMPLE_STANDARD_VARIANT_ITEM_ID
            ]
        );

        $this->executeUntilReady(ProductCreateTask::class, [
            LSR::SC_SUCCESS_CRON_PRODUCT
        ]);

        $this->assertCronSuccess(
            [
                LSR::SC_SUCCESS_CRON_PRODUCT,
            ],
            $storeId
        );

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
        $this->assertOffersWithItem(AbstractIntegrationTest::SAMPLE_OFFER_ITEM_1);
        $this->assertValidation();
        $this->assertDeletion();
    }

    public function assertValidation()
    {
        $scopeId = $this->storeManager->getWebsite()->getId();
        $storeId = $this->storeManager->getStore()->getId();
        $replDiscountValidation = $this->getDiscountValidationGivenId(
            AbstractIntegrationTest::SAMPLE_VALID_VALIDATION_PERIOD_ID,
            $scopeId
        );
        if ($replDiscountValidation) {
            $this->replDiscountValidationRepository->save(
                $replDiscountValidation->addData([
                    'is_updated' => 1,
                    'EndDate' => '2028-11-30'
                ])
            );

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
            $this->assertOffersWithItem(AbstractIntegrationTest::SAMPLE_OFFER_ITEM_1);
        }
    }
    public function assertDeletion()
    {
        $scopeId = $this->storeManager->getWebsite()->getId();
        $this->assertOfferDeletion(
            [
                'scope_id' => $scopeId,
                'OfferNo' => AbstractIntegrationTest::SAMPLE_OFFER_ITEM_1,
                'LineType' => OfferDiscountLineType::ITEM,
                'Number' => AbstractIntegrationTest::SAMPLE_DISCOUNTED_CONFIGURABLE_ITEM_ID,
                'VariantId' => null,
                'VariantType' => 0,
                'UnitOfMeasureId' => null,
            ]
        );

        $this->assertOfferDeletion(
            [
                'scope_id' => $scopeId,
                'OfferNo' => AbstractIntegrationTest::SAMPLE_OFFER_CATEGORY_1
            ]
        );
    }

    public function assertOfferDeletion($params)
    {
        $storeId  = $this->storeManager->getStore()->getId();
        $offerNo  = $params['OfferNo'];
        $lineType = $params['LineType'] ?? null;
        $offer1   = $this->getDiscountGivenOfferNo($params);

        if (!empty($offer1)) {
            $replDiscountSetup = current($offer1);
            $replDiscountSetup->addData([
                'Enabled' => 0,
                'is_updated' => 1
            ]);

            $this->replDiscountSetupRepository->save($replDiscountSetup);

            $this->executeUntilReady(DiscountCreateSetupTask::class, [
                LSR::SC_SUCCESS_CRON_DISCOUNT_SETUP
            ]);

            $this->assertCronSuccess(
                [
                    LSR::SC_SUCCESS_CRON_DISCOUNT_SETUP,
                ],
                $storeId
            );
        }

        if ($lineType == OfferDiscountLineType::ITEM) {
            $this->assertOffersWithItem($offerNo);
        } else {
            $this->assertOffersWithGroups($offerNo);
        }
    }

    public function addDummyData()
    {
        parent::addDummyData();
        $this->addDummyDiscountSetupData(
            AbstractIntegrationTest::SAMPLE_OFFER_ITEM_1,
            OfferDiscountLineType::ITEM,
            AbstractIntegrationTest::SAMPLE_DISCOUNTED_CONFIGURABLE_ITEM_ID,
            null,
            0,
            AbstractIntegrationTest::SAMPLE_UOM,
            AbstractIntegrationTest::ENABLED,
            '15.0000',
            '6.7500',
            AbstractIntegrationTest::SAMPLE_PRICE_GROUP,
            AbstractIntegrationTest::SAMPLE_STORE_GROUP_CODES,
            '30000',
            AbstractIntegrationTest::ENABLED,
            0,
            AbstractIntegrationTest::SAMPLE_VALID_VALIDATION_PERIOD_ID
        );

        $this->addDummyDiscountSetupData(
            AbstractIntegrationTest::SAMPLE_OFFER_ITEM_1,
            OfferDiscountLineType::ITEM,
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
            '001',
            AbstractIntegrationTest::ENABLED,
            AbstractIntegrationTest::SAMPLE_UOM_2,
            AbstractIntegrationTest::ENABLED,
            '15.0000',
            '72.0000',
            AbstractIntegrationTest::SAMPLE_PRICE_GROUP,
            AbstractIntegrationTest::SAMPLE_STORE_GROUP_CODES,
            '60000',
            AbstractIntegrationTest::ENABLED,
            0,
            AbstractIntegrationTest::SAMPLE_VALID_VALIDATION_PERIOD_ID
        );

        $this->addDummyDiscountSetupData(
            AbstractIntegrationTest::SAMPLE_OFFER_ITEM_1,
            OfferDiscountLineType::ITEM,
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
            '003',
            AbstractIntegrationTest::ENABLED,
            null,
            0,
            '15.0000',
            '12.0000',
            AbstractIntegrationTest::SAMPLE_PRICE_GROUP,
            AbstractIntegrationTest::SAMPLE_STORE_GROUP_CODES,
            '90000',
            AbstractIntegrationTest::ENABLED,
            0,
            AbstractIntegrationTest::SAMPLE_VALID_VALIDATION_PERIOD_ID
        );

        $this->addDummyDiscountSetupData(
            AbstractIntegrationTest::SAMPLE_OFFER_ITEM_1,
            OfferDiscountLineType::ITEM,
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
            '007',
            AbstractIntegrationTest::ENABLED,
            null,
            0,
            '15.0000',
            '12.0000',
            AbstractIntegrationTest::SAMPLE_PRICE_GROUP,
            AbstractIntegrationTest::SAMPLE_STORE_GROUP_CODES,
            '100000',
            AbstractIntegrationTest::ENABLED,
            0,
            AbstractIntegrationTest::SAMPLE_VALID_VALIDATION_PERIOD_ID
        );

        $this->addDummyDiscountSetupData(
            AbstractIntegrationTest::SAMPLE_OFFER_ITEM_1,
            OfferDiscountLineType::ITEM,
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
            'ORANGE',
            2,
            null,
            0,
            '15.0000',
            '12.0000',
            AbstractIntegrationTest::SAMPLE_PRICE_GROUP,
            AbstractIntegrationTest::SAMPLE_STORE_GROUP_CODES,
            '110000',
            AbstractIntegrationTest::ENABLED,
            0,
            AbstractIntegrationTest::SAMPLE_VALID_VALIDATION_PERIOD_ID
        );
    }

    public function addDummyDiscountSetupData(
        $offerNo,
        $lineType,
        $itemId,
        $variantId,
        $variantType,
        $uomOfMeasureId,
        $isPercentage,
        $dealPriceDiscount,
        $lineDiscountAmountIncVat,
        $priceGroup,
        $storeGroupCodes,
        $lineNumber,
        $isEnabled,
        $isDeleted,
        $validationPeriodId
    ) {
        $scopeId = $this->storeManager->getWebsite()->getId();
        $params  = [
            'scope_id' => $scopeId,
            'OfferNo' => $offerNo,
            'LineType' => $lineType,
            'Number' => $itemId,
            'VariantId' => $variantId,
            'VariantType' => $variantType,
            'UnitOfMeasureId' => $uomOfMeasureId,
        ];
        $offer1  = $this->getDiscountGivenOfferNo($params);

        if (empty($offer1)) {
            $replDiscountSetup = $this->replDiscountSetupInterfaceFactory->create();
            $replDiscountSetup->addData(
                [
                    'DealPriceDiscount' => $dealPriceDiscount,
                    'Description' => 'Denim on denim discount 15%',
                    'Details' => 'Denim on denim discount 15%',
                    'Enabled' => $isEnabled,
                    'IsDeleted' => $isDeleted,
                    'IsPercentage' => $isPercentage,
                    'LineDiscountAmountIncVAT' => $lineDiscountAmountIncVat,
                    'LineNumber' => $lineNumber,
                    'LinePriceGroup' => $priceGroup,
                    'LineType' => $lineType,
                    'LoyaltySchemeCode' => 'CRONUS',
                    'MemberType' => 'Club',
                    'Number' => $itemId,
                    'OfferNo' => $offerNo,
                    'PriceGroup' => $priceGroup,
                    'Type' => 'DiscOffer',
                    'UnitOfMeasureId' => $uomOfMeasureId,
                    'ValidationPeriodId' => $validationPeriodId,
                    'VariantId' => $variantId,
                    'VariantType' => $variantType,
                    'StoreGroupCodes' => $storeGroupCodes,
                    'scope' => ScopeInterface::SCOPE_WEBSITES,
                    'scope_id' => $this->storeManager->getWebsite()->getId()
                ]
            );

            $this->replDiscountSetupRepository->save($replDiscountSetup);
        }
    }

    public function assertOffersWithItem($offerNo)
    {
        $scopeId       = $this->storeManager->getWebsite()->getId();
        $params        = [
            'scope_id' => $scopeId,
            'OfferNo' => $offerNo,
            'LineType' => OfferDiscountLineType::ITEM
        ];
        $replDiscounts = $this->getDiscountGivenOfferNo($params);

        if (!empty($replDiscounts)) {
            foreach ($replDiscounts as $replDiscount) {
                $replDiscountValidation = $this->getDiscountValidationGivenId(
                    $replDiscount->getValidationPeriodId(),
                    $scopeId
                );

                $disabled    = $replDiscount->getIsDeleted() == 1 || $replDiscount->getEnabled() == 0;
                $catalogRule = $this->getCatalogRuleGivenReplDiscount($replDiscount);

                if ($catalogRule && $replDiscountValidation) {
                    $skuAmountArray = [];
                    try {
                        $this->discountCron->getItemsInRequiredFormat($replDiscount, $skuAmountArray);
                    } catch (\Exception $e) {
                        continue;
                    }

                    if (empty($skuAmountArray)) {
                        continue;
                    }
                    if (!$replDiscount->getIsPercentage()) {
                        $discountValueType = DiscountValueType::AMOUNT;
                        $discountValue     = $replDiscount->getLineDiscountAmountInclVAT();
                    } else {
                        $discountValueType = DiscountValueType::PERCENT;
                        $discountValue     = $replDiscount->getDealPriceDiscount();
                    }
                    if (isset($skuAmountArray[$discountValue][$discountValueType])) {
                        $this->assertRuleConditions(
                            $catalogRule,
                            $replDiscount,
                            $disabled,
                            $skuAmountArray[$discountValue][$discountValueType]
                        );
                    }
                    $this->assertCustomerGroups($catalogRule, $replDiscount);

                    $this->assertTrue($catalogRule->getDescription() == $replDiscount->getDescription());

                    if ($replDiscount->getIsPercentage()) {
                        $this->assertTrue($catalogRule->getSimpleAction() == 'by_percent');
                        $this->assertTrue($catalogRule->getDiscountAmount() == $replDiscount->getDiscountValue());
                    } else {
                        $this->assertTrue($catalogRule->getSimpleAction() == 'by_fixed');
                        $this->assertTrue(
                            $catalogRule->getDiscountAmount() == $replDiscount->getLineDiscountAmountInclVAT()
                        );
                    }
                    $this->assertDates($replDiscountValidation, $catalogRule);
                }
            }
        }
    }

    public function assertOffersWithGroups($offerNo)
    {
        $scopeId       = $this->storeManager->getWebsite()->getId();
        $params        = [
            'scope_id' => $scopeId,
            'OfferNo' => $offerNo
        ];
        $replDiscounts = $this->getDiscountGivenOfferNo($params);

        if (!empty($replDiscounts)) {
            foreach ($replDiscounts as $replDiscount) {
                $replDiscountValidation = $this->getDiscountValidationGivenId(
                    $replDiscount->getValidationPeriodId(),
                    $scopeId
                );
                $disabled               = $replDiscount->getIsDeleted() == 1 || $replDiscount->getEnabled() == 0;
                $catalogRule            = $this->getCatalogRuleGivenReplDiscount($replDiscount);

                if ($disabled) {
                    $this->assertFalse($catalogRule);
                }

                if ($catalogRule && $replDiscountValidation) {
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
    }

    public function assertCustomerGroups($catalogRule, $replDiscount)
    {
        $customerGroupsId     = $this->getCustomerGroupsIdGivenOffer($replDiscount);
        $ruleCustomerGroupsId = $catalogRule->getCustomerGroupIds();

        $this->assertEqualsCanonicalizing($customerGroupsId, $ruleCustomerGroupsId);
    }

    public function assertRuleConditions($catalogRule, $replDiscount, $disabled = false, $skuAmountArray = [])
    {
        if ($replDiscount->getLineType() == OfferDiscountLineType::SPECIAL_GROUP) {
            $attributeCode = LSR::LS_ITEM_SPECIAL_GROUP;
        } elseif ($replDiscount->getLineType() == OfferDiscountLineType::PRODUCT_GROUP) {
            $attributeCode = LSR::LS_ITEM_PRODUCT_GROUP;
        } elseif ($replDiscount->getLineType() == OfferDiscountLineType::ITEM_CATEGORY) {
            $attributeCode = LSR::LS_ITEM_CATEGORY;
        } else {
            $attributeCode = 'sku';
        }

        $specialGroupValue = $replDiscount->getNumber();

        $ruleConditions = $catalogRule->getRuleCondition();

        if ($ruleConditions && !empty($attributeCode)) {
            $condition = current($ruleConditions->getConditions());
            $this->assertTrue($condition->getAttribute() == $attributeCode);

            if ($condition->getOperator() == '{}') {
                $this->assertTrue($condition->getValue() == $specialGroupValue . ';');
            } elseif ($condition->getOperator() == '()') {
                $skus = explode(',', $condition->getValue());

                foreach ($skuAmountArray as $sku) {
                    if ($disabled) {
                        $this->assertFalse(in_array($sku, $skus));
                    } else {
                        $this->assertTrue(in_array($sku, $skus));
                    }
                }
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
        $simpleAction = '';

        if ($replDiscount->getLineType() != OfferDiscountLineType::ITEM) {
            $offerName = $replDiscount->getOfferNo() . '-' . $replDiscount->getLineNumber();
        } else {
            $offerName    = $replDiscount->getOfferNo();
            $simpleAction = $replDiscount->getIsPercentage() ? 'by_percent' : 'by_fixed';
        }

        $catalogRule = $this->getRuleByName($offerName, $simpleAction);

        return $catalogRule;
    }

    public function getRuleByName(string $name, string $simpleAction = '')
    {
        $catalogRuleResource = $this->catalogRuleResource->create();
        $select              = $catalogRuleResource->getConnection()->select();
        $select->from($catalogRuleResource->getMainTable(), RuleInterface::RULE_ID);
        $select->where(RuleInterface::NAME . ' = ?', $name);

        if (!empty($simpleAction)) {
            $select->where(RuleInterface::SIMPLE_ACTION . ' = ?', $simpleAction);
        }
        $ruleId = $catalogRuleResource->getConnection()->fetchOne($select);

        if (!$ruleId) {
            return false;
        }
        return $this->catalogRuleRepository->get((int)$ruleId);
    }

    public function getDiscountGivenOfferNo($params)
    {
        $filters = [];

        foreach ($params as $val => $param) {
            if ($param != null) {
                $filters[] = ['field' => $val, 'value' => $param, 'condition_type' => 'eq'];
            }
        }
        $criteria = $this->replicationHelper->buildCriteriaForDirect($filters, -1, 1);
        /** @var ReplDiscountSearchResults $replDiscounts */
        $replDiscounts = $this->replDiscountSetupRepository->getList($criteria)->getItems();

        return $replDiscounts;
    }

    public function getDiscountValidationGivenId($validationId, $scopeId)
    {
        $filters  = [
            ['field' => 'scope_id', 'value' => $scopeId, 'condition_type' => 'eq'],
            [
                'field' => 'nav_id',
                'value' => $validationId,
                'condition_type' => 'eq'
            ]
        ];
        $criteria = $this->replicationHelper->buildCriteriaForDirect($filters, -1);
        /** @var ReplDiscountValidation $replDiscountValidation */
        $replDiscountValidation = current($this->replDiscountValidationRepository->getList($criteria)->getItems());

        return $replDiscountValidation;
    }

    public function getCustomerGroupsIdGivenOffer($item)
    {
        $schemes = $this->contactHelper->getSchemes();

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
            $customerGroupIds = $this->discountCron->getRequiredCustomerGroups($item, $schemes);
        }

        return $customerGroupIds;
    }
}
