<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Cron\DiscountCreateTask;
use \Ls\Replication\Cron\ProductCreateTask;
use \Ls\Replication\Cron\ReplEcommAttributeOptionValueTask;
use \Ls\Replication\Cron\ReplEcommAttributeTask;
use \Ls\Replication\Cron\ReplEcommAttributeValueTask;
use \Ls\Replication\Cron\ReplEcommBarcodesTask;
use \Ls\Replication\Cron\ReplEcommDataTranslationLangCodeTask;
use \Ls\Replication\Cron\ReplEcommDataTranslationTask;
use \Ls\Replication\Cron\ReplEcommDealHtmlTranslationTask;
use \Ls\Replication\Cron\ReplEcommDiscountValidationsTask;
use \Ls\Replication\Cron\ReplEcommExtendedVariantsTask;
use \Ls\Replication\Cron\ReplEcommHierarchyLeafTask;
use \Ls\Replication\Cron\ReplEcommHierarchyNodeTask;
use \Ls\Replication\Cron\ReplEcommImageLinksTask;
use \Ls\Replication\Cron\ReplEcommInventoryStatusTask;
use \Ls\Replication\Cron\ReplEcommItemsTask;
use \Ls\Replication\Cron\ReplEcommItemUnitOfMeasuresTask;
use \Ls\Replication\Cron\ReplEcommItemVariantRegistrationsTask;
use \Ls\Replication\Cron\ReplEcommItemVariantsTask;
use \Ls\Replication\Cron\ReplEcommPricesTask;
use \Ls\Replication\Cron\ReplEcommUnitOfMeasuresTask;
use \Ls\Replication\Cron\ReplEcommVendorItemMappingTask;
use \Ls\Replication\Cron\ReplEcommVendorTask;
use \Ls\Replication\Model\ReplDiscountSearchResults;
use \Ls\Replication\Test\Fixture\FlatDataReplication;
use \Ls\Replication\Test\Integration\AbstractIntegrationTest;
use Magento\CatalogRule\Api\Data\RuleInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;

/**
 * @magentoAppArea crontab
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class DiscountCreateTaskTest extends AbstractTaskTest
{
    public $discountCron;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->discountCron = $this->objectManager->get(DiscountCreateTask::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @magentoDbIsolation enabled
     */
    #[
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommAttributeTask::class,
                'scope' => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommAttributeOptionValueTask::class,
                'scope' => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommAttributeValueTask::class,
                'scope' => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommExtendedVariantsTask::class,
                'scope' => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommItemVariantsTask::class,
                'scope' => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommUnitOfMeasuresTask::class,
                'scope' => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommItemUnitOfMeasuresTask::class,
                'scope' => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommVendorTask::class,
                'scope' => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommHierarchyNodeTask::class,
                'scope' => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommHierarchyLeafTask::class,
                'scope' => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommItemsTask::class,
                'scope' => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommBarcodesTask::class,
                'scope' => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommItemVariantRegistrationsTask::class,
                'scope' => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommPricesTask::class,
                'scope' => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommImageLinksTask::class,
                'scope' => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommInventoryStatusTask::class,
                'scope' => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommVendorTask::class,
                'scope' => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommVendorItemMappingTask::class,
                'scope' => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommDiscountValidationsTask::class,
                'scope' => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommDataTranslationLangCodeTask::class,
                'scope' => ScopeInterface::SCOPE_STORE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommDataTranslationTask::class,
                'scope' => ScopeInterface::SCOPE_STORE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommDealHtmlTranslationTask::class,
                'scope' => ScopeInterface::SCOPE_STORE
            ]
        ),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, '2023.0.0', 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
        Config(LSR::SC_SERVICE_VERSION, '2023.0.0', 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(
            LSR::SC_STORE_DATA_TRANSLATION_LANG_CODE,
            AbstractIntegrationTest::SAMPLE_LANGUAGE_CODE,
            'store',
            'default'
        ),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'website'),
        Config(LSR::SC_REPLICATION_DEFAULT_BATCHSIZE, AbstractIntegrationTest::DEFAULT_BATCH_SIZE),
        Config(
            LSR::SC_REPLICATION_HIERARCHY_CODE,
            AbstractIntegrationTest::SAMPLE_HIERARCHY_NAV_ID,
            'store',
            'default'
        ),
        Config(
            LSR::SC_REPLICATION_HIERARCHY_CODE,
            AbstractIntegrationTest::SAMPLE_HIERARCHY_NAV_ID,
            'website'
        ),
        Config(LSR::SC_REPLICATION_PRODUCT_BATCHSIZE, 5, 'store', 'default')
    ]
    public function testExecute()
    {
        parent::testExecute();
    }

    public function actualExecute()
    {
        $storeId                   = $this->storeManager->getStore()->getId();
        $this->discountCron->store = $this->storeManager->getStore();
        $this->updateAllRelevantItemRecords(
            1,
            [
                AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
                AbstractIntegrationTest::SAMPLE_DISCOUNTED_CONFIGURABLE_ITEM_ID,
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

        $this->executeUntilReady(DiscountCreateTask::class, [
            LSR::SC_SUCCESS_CRON_DISCOUNT
        ]);

        $this->assertCronSuccess(
            [
                LSR::SC_SUCCESS_CRON_DISCOUNT,
            ],
            $storeId
        );

        $this->assertOffersWithItem(AbstractIntegrationTest::SAMPLE_OFFER_ITEM_1);
        $this->assertValidation(AbstractIntegrationTest::SAMPLE_OFFER_ITEM_1);
        $this->assertDeletion(AbstractIntegrationTest::SAMPLE_OFFER_ITEM_1);
    }

    public function assertValidation($offerNo)
    {
        $scopeId       = $this->storeManager->getWebsite()->getId();
        $storeId       = $this->storeManager->getStore()->getId();
        $params        = [
            'scope_id' => $scopeId,
            'OfferNo' => $offerNo
        ];
        $replDiscounts = $this->getDiscountGivenOfferNo($params);
        if (!empty($replDiscounts)) {
            foreach ($replDiscounts as $replDiscount) {
                $this->replDiscountRepository->save(
                    $replDiscount->addData([
                        'is_updated' => 1,
                        'ToDate' => '2028-10-31T00:00:00'
                    ])
                );
            }

            $this->executeUntilReady(DiscountCreateTask::class, [
                LSR::SC_SUCCESS_CRON_DISCOUNT
            ]);

            $this->assertCronSuccess(
                [
                    LSR::SC_SUCCESS_CRON_DISCOUNT,
                ],
                $storeId
            );

            $this->assertOffersWithItem(AbstractIntegrationTest::SAMPLE_OFFER_ITEM_1);
        }
    }

    public function assertDeletion($offerNo)
    {
        $scopeId       = $this->storeManager->getWebsite()->getId();
        $storeId       = $this->storeManager->getStore()->getId();
        $params        = [
            'scope_id' => $scopeId,
            'OfferNo' => $offerNo
        ];
        $replDiscounts = $this->getDiscountGivenOfferNo($params);
        if (!empty($replDiscounts)) {
            foreach ($replDiscounts as $replDiscount) {
                $this->replDiscountRepository->save(
                    $replDiscount->addData([
                        'IsDeleted' => 1,
                        'is_updated' => 1
                    ])
                );
            }

            $this->executeUntilReady(DiscountCreateTask::class, [
                LSR::SC_SUCCESS_CRON_DISCOUNT
            ]);

            $this->assertCronSuccess(
                [
                    LSR::SC_SUCCESS_CRON_DISCOUNT,
                ],
                $storeId
            );

            $catalogRule = $this->getRuleByName($offerNo);

            $this->assertFalse($catalogRule);
        }
    }

    public function addDummyData()
    {
        parent::addDummyData();
        $this->addDummyDiscountData(
            AbstractIntegrationTest::SAMPLE_OFFER_ITEM_1,
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
            '001',
            'PACK',
            0,
            AbstractIntegrationTest::SAMPLE_VALID_VALIDATION_PERIOD_ID,
            '15.0000',
        );

        $this->addDummyDiscountData(
            AbstractIntegrationTest::SAMPLE_OFFER_ITEM_1,
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
            '003',
            null,
            0,
            AbstractIntegrationTest::SAMPLE_VALID_VALIDATION_PERIOD_ID,
            '15.0000',
        );

        $this->addDummyDiscountData(
            AbstractIntegrationTest::SAMPLE_OFFER_ITEM_1,
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
            '007',
            null,
            0,
            AbstractIntegrationTest::SAMPLE_VALID_VALIDATION_PERIOD_ID,
            '15.0000',
        );
        $this->addDummyDiscountData(
            AbstractIntegrationTest::SAMPLE_OFFER_ITEM_1,
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
            '021',
            null,
            0,
            AbstractIntegrationTest::SAMPLE_VALID_VALIDATION_PERIOD_ID,
            '15.0000',
        );
        $this->addDummyDiscountData(
            AbstractIntegrationTest::SAMPLE_OFFER_ITEM_1,
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
            '022',
            null,
            0,
            AbstractIntegrationTest::SAMPLE_VALID_VALIDATION_PERIOD_ID,
            '15.0000',
        );
        $this->addDummyDiscountData(
            AbstractIntegrationTest::SAMPLE_OFFER_ITEM_1,
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
            '023',
            null,
            0,
            AbstractIntegrationTest::SAMPLE_VALID_VALIDATION_PERIOD_ID,
            '15.0000',
        );
        $this->addDummyDiscountData(
            AbstractIntegrationTest::SAMPLE_OFFER_ITEM_1,
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
            '024',
            null,
            0,
            AbstractIntegrationTest::SAMPLE_VALID_VALIDATION_PERIOD_ID,
            '15.0000',
        );
        $this->addDummyDiscountData(
            AbstractIntegrationTest::SAMPLE_OFFER_ITEM_1,
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
            '025',
            null,
            0,
            AbstractIntegrationTest::SAMPLE_VALID_VALIDATION_PERIOD_ID,
            '15.0000',
        );
        $this->addDummyDiscountData(
            AbstractIntegrationTest::SAMPLE_OFFER_ITEM_1,
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
            '026',
            null,
            0,
            AbstractIntegrationTest::SAMPLE_VALID_VALIDATION_PERIOD_ID,
            '15.0000',
        );

        $this->addDummyDiscountData(
            AbstractIntegrationTest::SAMPLE_OFFER_ITEM_1,
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
            '027',
            null,
            0,
            AbstractIntegrationTest::SAMPLE_VALID_VALIDATION_PERIOD_ID,
            '15.0000',
        );
    }

    public function addDummyDiscountData(
        $offerNo,
        $itemId,
        $variantId,
        $uomOfMeasureId,
        $isDeleted,
        $validationPeriodId,
        $discountValue
    ) {
        $scopeId           = $this->storeManager->getWebsite()->getId();
        $loyaltySchemeCode = ['CR1-BRONZE', 'CR2-SILVER', 'CR3-GOLD'];

        foreach ($loyaltySchemeCode as $scheme) {
            $params  = [
                'scope_id' => $scopeId,
                'OfferNo' => $offerNo,
                'ItemId' => $itemId,
                'VariantId' => $variantId,
                'UnitOfMeasureId' => $uomOfMeasureId,
                'LoyaltySchemeCode' => $scheme,
            ];
            $replDiscount  = $this->getDiscountGivenOfferNo($params);

            if (empty($replDiscount)) {
                $replDiscount = $this->replDiscountInterfaceFactory->create();
                $replDiscount->addData(
                    [
                        'Description' => 'Denim on denim discount 15%',
                        'Details' => 'Denim on denim discount 15%',
                        'DiscountValue' => $discountValue,
                        'DiscountValueType' => 'DealPrice',
                        'FromDate' => '2022-01-01T00:00:00',
                        'IsDeleted' => $isDeleted,
                        'ItemId' => $itemId,
                        'LoyaltySchemeCode' => $scheme,
                        'MinimumQuantity' => '0.0000',
                        'OfferNo' => $offerNo,
                        'StoreId' => AbstractIntegrationTest::CS_STORE,
                        'ToDate' => '2028-12-31T00:00:00',
                        'Type' => 'DiscOffer',
                        'UnitOfMeasureId' => $uomOfMeasureId,
                        'ValidationPeriodId' => $validationPeriodId,
                        'VariantId' => $variantId,
                        'scope' => ScopeInterface::SCOPE_WEBSITES,
                        'scope_id' => $this->storeManager->getWebsite()->getId()
                    ]
                );
            } else {
                $replDiscount = current($replDiscount);
                $replDiscount->addData([
                    'IsDeleted' => $isDeleted,
                    'processed' => 0
                ]);
            }

            $this->replDiscountRepository->save($replDiscount);
        }
    }

    public function assertOffersWithItem($offerNo)
    {
        $scopeId       = $this->storeManager->getWebsite()->getId();
        $params        = [
            'scope_id' => $scopeId,
            'OfferNo' => $offerNo
        ];
        $replDiscounts = $this->getDiscountGivenOfferNo($params);
        if (!empty($replDiscounts)) {
            foreach ($replDiscounts as $replDiscount) {
                $disabled    = $replDiscount->getIsDeleted() == 1;
                $catalogRule = $this->getCatalogRuleGivenReplDiscount($replDiscount);

                if ($catalogRule) {

                    $skuAmountArray = [];
                    try {
                        $this->discountCron->getItemsInRequiredFormat($replDiscount, $skuAmountArray);
                    } catch (\Exception $e) {
                        continue;
                    }

                    if (empty($skuAmountArray)) {
                        continue;
                    }
                    $discountValue     = $replDiscount->getDiscountValue();
                    if (isset($skuAmountArray[$discountValue])) {
                        $this->assertRuleConditions(
                            $catalogRule,
                            $disabled,
                            $skuAmountArray[$discountValue]
                        );
                    }
                    $this->assertCustomerGroups($catalogRule, $replDiscount);
                    $this->assertTrue($catalogRule->getDescription() == $replDiscount->getDescription());

                    $this->assertDates($replDiscount, $catalogRule);

                    $simpleAction = $replDiscount->getDiscountValueType() == 'Amount' ? 'by_fixed' : 'by_percent';

                    $this->assertTrue($catalogRule->getSimpleAction() == $simpleAction);
                    $this->assertTrue($catalogRule->getDiscountAmount() == $replDiscount->getDiscountValue());
                }
            }
        }
    }

    public function assertCustomerGroups($catalogRule, $replDiscount)
    {
        $customerGroupsId     = $this->getCustomerGroupsIdGivenOffer($replDiscount);
        $ruleCustomerGroupsId = $catalogRule->getCustomerGroupIds();

        $this->assertTrue(empty(array_diff($customerGroupsId, $ruleCustomerGroupsId)));
    }

    public function assertRuleConditions($catalogRule, $disabled = false, $skuAmountArray = [])
    {
        $attributeCode = 'sku';

        $ruleConditions = $catalogRule->getRuleCondition();

        if ($ruleConditions && !empty($attributeCode)) {
            $condition = current($ruleConditions->getConditions());
            $this->assertTrue($condition->getAttribute() == $attributeCode);

            if ($condition->getOperator() == '()') {
                $skus = explode(',', $condition->getValue());

                foreach ($skuAmountArray as $sku) {
                    if ($disabled) {
                        $this->assertFalse(in_array($sku, $skus));
                    } else {
                        $this->assertTrue(in_array($sku, $skus));
                    }
                }
            }
        }
    }

    public function assertDates($replDiscount, $catalogRule)
    {
        $fromDate = $replDiscount->getFromDate();
        $toDate   = $replDiscount->getToDate();

        if (!empty($fromDate)) {
            $this->assertTrue(substr($fromDate, 0, strpos($fromDate, 'T')) == $catalogRule->getFromDate());
        }

        if (strtolower($toDate ?? '') != strtolower('1753-01-01T00:00:00')
            && !empty($toDate)) {
            $this->assertTrue(substr($toDate, 0, strpos($toDate, 'T')) == $catalogRule->getToDate());
        }
    }

    public function getCatalogRuleGivenReplDiscount($replDiscount)
    {
        $offerName    = $replDiscount->getOfferNo();

        $simpleAction = $replDiscount->getDiscountValueType() == 'Amount' ? 'by_fixed' : 'by_percent';

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
        $replDiscounts = $this->replDiscountRepository->getList($criteria)->getItems();

        return $replDiscounts;
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
