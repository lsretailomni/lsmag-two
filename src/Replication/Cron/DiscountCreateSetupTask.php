<?php

namespace Ls\Replication\Cron;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\DiscountValueType;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\OfferDiscountLineType;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\ReplDiscountType;
use \Ls\Omni\Helper\ContactHelper;
use \Ls\Replication\Api\ReplDiscountSetupRepositoryInterface;
use \Ls\Replication\Api\ReplDiscountValidationRepositoryInterface;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Logger\Logger;
use \Ls\Replication\Model\ReplDiscountSearchResults;
use \Ls\Replication\Model\ReplDiscountSetup;
use \Ls\Replication\Model\ReplDiscountValidation;
use \Ls\Replication\Model\ResourceModel\ReplDiscountSetup\Collection;
use \Ls\Replication\Model\ResourceModel\ReplDiscountSetup\CollectionFactory;
use Magento\CatalogRule\Api\CatalogRuleRepositoryInterface;
use Magento\CatalogRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use Magento\CatalogRule\Model\Rule\Condition\Combine;
use Magento\CatalogRule\Model\Rule\Condition\Product;
use Magento\CatalogRule\Model\Rule\Job;
use Magento\CatalogRule\Model\RuleFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InvalidTransitionException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Discount creation for discount offers support items, item category, product group, All
 */
class DiscountCreateSetupTask
{
    /**
     * @var CatalogRuleRepositoryInterface
     */
    public $catalogRule;

    /**
     * @var RuleFactory
     */
    public $ruleFactory;

    /**
     * @var RuleCollectionFactory
     */
    public $ruleCollectionFactory;

    /**
     * @var Job
     */
    public $jobApply;

    /**
     * @var ReplDiscountSetupRepositoryInterface
     */
    public $replDiscountRepository;

    /**
     * @var ReplDiscountValidationRepositoryInterface
     */
    public $discountValidationRepository;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * @var Logger
     */
    public $logger;

    /**
     * @var ReplicationHelper
     */
    public $replicationHelper;

    /**
     * @var ContactHelper
     */
    public $contactHelper;

    /**
     * @var CollectionFactory
     */
    public $replDiscountCollection;

    /** @var int */
    public $remainingRecords;

    /**
     * @var StoreInterface
     */
    public $store;

    /**
     * @param CatalogRuleRepositoryInterface $catalogRule
     * @param RuleFactory $ruleFactory
     * @param RuleCollectionFactory $ruleCollectionFactory
     * @param Job $jobApply
     * @param ReplDiscountSetupRepositoryInterface $replDiscountRepository
     * @param ReplDiscountValidationRepositoryInterface $discountValidationRepository
     * @param ReplicationHelper $replicationHelper
     * @param LSR $LSR
     * @param CollectionFactory $replDiscountCollection
     * @param ContactHelper $contactHelper
     * @param Logger $logger
     */
    public function __construct(
        CatalogRuleRepositoryInterface $catalogRule,
        RuleFactory $ruleFactory,
        RuleCollectionFactory $ruleCollectionFactory,
        Job $jobApply,
        ReplDiscountSetupRepositoryInterface $replDiscountRepository,
        ReplDiscountValidationRepositoryInterface $discountValidationRepository,
        ReplicationHelper $replicationHelper,
        LSR $LSR,
        CollectionFactory $replDiscountCollection,
        ContactHelper $contactHelper,
        Logger $logger
    ) {
        $this->catalogRule                  = $catalogRule;
        $this->ruleFactory                  = $ruleFactory;
        $this->ruleCollectionFactory        = $ruleCollectionFactory;
        $this->jobApply                     = $jobApply;
        $this->replDiscountRepository       = $replDiscountRepository;
        $this->discountValidationRepository = $discountValidationRepository;
        $this->replicationHelper            = $replicationHelper;
        $this->contactHelper                = $contactHelper;
        $this->lsr                          = $LSR;
        $this->replDiscountCollection       = $replDiscountCollection;
        $this->logger                       = $logger;
    }

    /**
     * Discount Creation
     *
     * @param mixed $storeData
     * @return void
     * @throws InputException
     * @throws InvalidTransitionException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute($storeData = null)
    {
        /**
         * Get all Unique Publish offer so that we can create catalog rules based on that.
         * And the web store is being set in the Magento.
         * And we need to apply only those rules which are associated to the store assigned to it.
         */
        if (!$this->replicationHelper->isSSM()) {
            if (!empty($storeData) && $storeData instanceof StoreInterface) {
                $stores = [$storeData];
            } else {
                $stores = $this->lsr->getAllStores();
            }
        } else {
            $stores = [$this->lsr->getAdminStore()];
        }

        if (!empty($stores)) {
            foreach ($stores as $store) {
                if (!$this->lsr->validateForOlderVersion($store)['discountSetup']) {
                    continue;
                }
                $this->lsr->setStoreId($store->getId());
                $this->store = $store;
                if ($this->lsr->isLSR($this->store->getId())) {

                    $fullReplicationDiscountValidationStatus = $this->lsr->getConfigValueFromDb(
                        ReplEcommDiscountValidationsTask::CONFIG_PATH_STATUS,
                        ScopeInterface::SCOPE_WEBSITES,
                        $this->getScopeId()
                    );
                    if ($fullReplicationDiscountValidationStatus) {
                        $this->logger->debug('Running DiscountCreateTask for store ' . $this->store->getName());
                        $this->replicationHelper->updateConfigValue(
                            $this->replicationHelper->getDateTime(),
                            LSR::SC_CRON_DISCOUNT_CONFIG_PATH_LAST_EXECUTE_SETUP,
                            $this->store->getId(),
                            ScopeInterface::SCOPE_STORES
                        );
                        $storeId                  = $this->getScopeId();
                        $publishedOfferCollection = $this->getUniquePublishedOffers($storeId);
                        if (!empty($publishedOfferCollection)) {
                            $reindexRules = false;
                            /** @var ReplDiscountSetup $item */
                            foreach ($publishedOfferCollection as $item) {
                                $filters  = [
                                    ['field' => 'scope_id', 'value' => $storeId, 'condition_type' => 'eq'],
                                    [
                                        'field'          => 'nav_id',
                                        'value'          => $item->getValidationPeriodId(),
                                        'condition_type' => 'eq'
                                    ]
                                ];
                                $criteria = $this->replicationHelper->buildCriteriaForDirect($filters, -1);
                                /** @var ReplDiscountValidation $replDiscountValidation */
                                $replDiscountValidation = $this->discountValidationRepository->getList($criteria);
                                $filters                = [
                                    ['field' => 'scope_id', 'value' => $storeId, 'condition_type' => 'eq'],
                                    ['field' => 'OfferNo', 'value' => $item->getOfferNo(), 'condition_type' => 'eq'],
                                    [
                                        'field'          => 'Type',
                                        'value'          => ReplDiscountType::DISC_OFFER,
                                        'condition_type' => 'eq'
                                    ]
                                ];

                                $criteria = $this->replicationHelper->buildCriteriaForArray($filters, -1, 1);
                                /** @var ReplDiscountSearchResults $replDiscounts */
                                $replDiscounts            = $this->replDiscountRepository->getList($criteria);
                                $skuAmountArray           = [];
                                $categoryGroupAmountArray = [];
                                if ($item->getLoyaltySchemeCode() == '' ||
                                    $item->getLoyaltySchemeCode() == null
                                ) {
                                    $useAllGroupIds   = true;
                                    $customerGroupIds = $this->contactHelper->getAllCustomerGroupIds();
                                } else {
                                    $useAllGroupIds   = false;
                                    $customerGroupIds = [];
                                }

                                /** @var ReplDiscountSetup $replDiscount */
                                foreach ($replDiscounts->getItems() as $replDiscount) {
                                    if (!$replDiscount->getIsPercentage()) {
                                        $discountValueType = DiscountValueType::AMOUNT;
                                        $discountValue     = $replDiscount->getLineDiscountAmountInclVAT();
                                    } else {
                                        $discountValueType = DiscountValueType::PERCENT;
                                        $discountValue     = $replDiscount->getDealPriceDiscount();
                                    }
                                    $this->deleteOfferByName($replDiscount);
                                    $customerGroupId = $this->contactHelper->getCustomerGroupIdByName(
                                        $replDiscount->getLoyaltySchemeCode()
                                    );

                                    // To check if discounts groups are specific for any Member Scheme.
                                    if (!$useAllGroupIds && !in_array($customerGroupId, $customerGroupIds)) {
                                        $customerGroupIds[] = $this->contactHelper->getCustomerGroupIdByName(
                                            $replDiscount->getLoyaltySchemeCode()
                                        );
                                    }

                                    $lineType = (string)$replDiscount->getLineType();

                                    if ($lineType == OfferDiscountLineType::ITEM) {
                                        $appendUom = '';
                                        if (!empty($replDiscount->getUnitOfMeasureId())) {
                                            // @codingStandardsIgnoreLine
                                            $baseUnitOfMeasure = $this->replicationHelper->getBaseUnitOfMeasure(
                                                $replDiscount->getNumber()
                                            );
                                            if (($baseUnitOfMeasure != $replDiscount->getUnitOfMeasureId()) ||
                                                ($replDiscount->getVariantId() == '' ||
                                                    $replDiscount->getVariantId() == null)) {
                                                $appendUom = '-' . $replDiscount->getUnitOfMeasureId();
                                            }
                                        }

                                        if ($replDiscount->getVariantId() == '' ||
                                            $replDiscount->getVariantId() == null
                                        ) {
                                            $skuAmountArray[$discountValue][$discountValueType] [] =
                                                $replDiscount->getNumber() . $appendUom;
                                        } else {
                                            $skuAmountArray[$discountValue][$discountValueType] [] =
                                                $replDiscount->getNumber() . '-' .
                                                $replDiscount->getVariantId() . $appendUom;
                                        }
                                    } else {
                                        $categoryGroupAmountArray[$discountValue][] = $replDiscount;
                                    }
                                    $replDiscount->setData('processed_at', $this->replicationHelper->getDateTime());
                                    $replDiscount->setData('processed', '1');
                                    $replDiscount->setData('is_updated', '0');
                                    // @codingStandardsIgnoreStart
                                    $this->replDiscountRepository->save($replDiscount);
                                    // @codingStandardsIgnoreEnd
                                }

                                if (!empty($skuAmountArray)) {
                                    foreach ($skuAmountArray as $value => $keys) {
                                        foreach ($keys as $index => $key) {
                                            $this->addSalesRule(
                                                $item,
                                                array_unique($key),
                                                $customerGroupIds,
                                                $replDiscountValidation,
                                                $index,
                                                (float)$value
                                            );
                                        }
                                        $reindexRules = true;
                                    }
                                }
                                if (!empty($categoryGroupAmountArray)) {
                                    foreach ($categoryGroupAmountArray as $value => $keys) {
                                        foreach ($keys as $key) {
                                            $this->addSalesRule(
                                                $item,
                                                $key,
                                                $customerGroupIds,
                                                $replDiscountValidation,
                                                '',
                                                (float)$value
                                            );
                                        }
                                    }
                                    $reindexRules = true;
                                }
                            }
                            if ($reindexRules) {
                                $this->jobApply->applyAll();
                            }
                            if ($this->getRemainingRecords($this->store->getId()) == 0) {
                                $this->replicationHelper->updateCronStatus(
                                    true,
                                    LSR::SC_SUCCESS_CRON_DISCOUNT_SETUP,
                                    $this->store->getId(),
                                    false,
                                    ScopeInterface::SCOPE_STORES
                                );
                            } else {
                                $this->replicationHelper->updateCronStatus(
                                    false,
                                    LSR::SC_SUCCESS_CRON_DISCOUNT_SETUP,
                                    $this->store->getId(),
                                    false,
                                    ScopeInterface::SCOPE_STORES
                                );
                            }
                        } else {
                            // set the status to success if there is nothing to process.
                            $this->replicationHelper->updateCronStatus(
                                true,
                                LSR::SC_SUCCESS_CRON_DISCOUNT_SETUP,
                                $this->store->getId(),
                                false,
                                ScopeInterface::SCOPE_STORES
                            );
                        }
                        /* Delete the IsDeleted offers */
                        $this->deleteOffers();
                        /* Synchronize validation period */
                        $this->syncValidationPeriod();
                        $this->logger->debug('End DiscountCreateTask for store ' . $this->store->getName());
                    }
                }
                $this->lsr->setStoreId(null);
            }
        }
    }

    /**
     * Execute Manually
     *
     * @param mixed $storeData
     * @return int[]
     * @throws InputException
     * @throws InvalidTransitionException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function executeManually(
        $storeData = null
    ) {
        $this->execute($storeData);
        $discountsLeftToProcess = $this->getRemainingRecords($storeData->getId());
        return [$discountsLeftToProcess];
    }

    /**
     * Add new catalog rule
     *
     * @param ReplDiscountSetup $replDiscount
     * @param mixed $key
     * @param mixed $customerGroupIds
     * @param mixed $discountValidation
     * @param string $discountValueType
     * @param mixed $amount
     * @return void
     * @throws Exception
     */
    public function addSalesRule(
        ReplDiscountSetup $replDiscount,
        $key,
        $customerGroupIds,
        $discountValidation,
        $discountValueType,
        $amount = null
    ) {
        $websiteIds = [$replDiscount->getScopeId()];
        if ($amount == null) {
            $amount = $replDiscount->getDiscountValue();
        }

        if ($key instanceof ReplDiscountSetup) {
            $name              = $key->getOfferNo() . '-' . $key->getLineNumber();
            $discountValueType = (!$key->getIsPercentage()) ? DiscountValueType::AMOUNT : DiscountValueType::PERCENT;
        } else {
            $name = $replDiscount->getOfferNo();
        }

        $conditions = $this->getConditions($key);
        $rule       = $this->ruleFactory->create();
        $fromDate   = '';
        $toDate     = '';
        if (!empty($discountValidation)) {
            foreach ($discountValidation->getItems() as $disValidation) {
                $fromDate = $disValidation->getStartDate();
                $toDate   = $disValidation->getEndDate();
            }
        }
        $rule->setName($name)
            ->setDescription($replDiscount->getDescription())
            ->setIsActive(1)
            ->setCustomerGroupIds($customerGroupIds)
            ->setWebsiteIds($websiteIds)
            ->setFromDate(($fromDate) ?: $this->replicationHelper->getCurrentDate());

        if (strtolower($toDate ?? '') != strtolower('1753-01-01T00:00:00')
            && !empty($toDate)) {
            $rule->setToDate($toDate);
        }

        /**
         * Default Values for Action Types.
         * by_percent
         * by_fixed
         * to_percent
         * to_fixed
         */
        if ($discountValueType == 'Amount') {
            $type = 'by_fixed';
        } else {
            $type = 'by_percent';
        }
        $rule->setSimpleAction($type)
            ->setDiscountAmount($amount)
            ->setStopRulesProcessing(1)
            ->setSortOrder($replDiscount->getPriorityNo());
        $rule->setData('conditions', $conditions);
        // @codingStandardsIgnoreLine
        $validateResult = $rule->validateData(new DataObject($rule->getData()));

        if ($validateResult !== true) {
            foreach ($validateResult as $errorMessage) {
                $this->logger->debug($errorMessage);
            }
            return;
        }
        try {
            $rule->loadPost($rule->getData());
            $this->catalogRule->save($rule);
        } catch (Exception $e) {
            $this->logDetailedException(__METHOD__, $this->store->getName(), $replDiscount->getOfferNo());
            $this->logger->debug($e->getMessage());
            $replDiscount->setData('is_failed', 1);
            // @codingStandardsIgnoreLine
            $this->replDiscountRepository->save($replDiscount);
        }
    }

    /**
     * Get unique published offers
     *
     * @param string $storeId
     * @return array|Collection
     */
    public function getUniquePublishedOffers(
        $storeId
    ) {
        $publishedOfferIds = [];
        /** @var  Collection $collection */
        $collection = $this->replDiscountCollection->create();
        $collection->addFieldToFilter('scope_id', $storeId);
        $collection->getSelect()
            ->columns(['OfferNo', 'ValidationPeriodId'])
            ->group('OfferNo');

        $collection->addFieldToFilter(
            'Type',
            ReplDiscountType::DISC_OFFER
        );

        $collection->addFieldToFilter(
            'Enabled',
            1
        );

        if ($collection->getSize() > 0) {
            return $collection;
        }
        return $publishedOfferIds;
    }

    /**
     * Delete all the Offers by OfferNo with IsDeleted = 1
     */
    public function deleteOffers()
    {
        $filters  = [
            ['field' => 'is_updated', 'value' => 1, 'condition_type' => 'eq'],
            ['field' => 'Type', 'value' => ReplDiscountType::DISC_OFFER, 'condition_type' => 'eq'],
            ['field' => 'scope_id', 'value' => $this->getScopeId(), 'condition_type' => 'eq']
        ];
        $criteria = $this->replicationHelper->buildCriteriaForDirect(
            $filters,
            -1,
            false,
            ['field' => 'Enabled', 'value' => 0, 'condition_type' => 'eq'],
            ['field' => 'IsDeleted', 'value' => 1, 'condition_type' => 'eq'],
        );
        /** @var ReplDiscountSearchResults $replDiscounts */
        $replDiscounts = $this->replDiscountRepository->getList($criteria);
        /** @var ReplDiscountSetup $replDiscount */
        foreach ($replDiscounts->getItems() as $replDiscount) {
            $this->deleteOfferByName($replDiscount);
            $replDiscount->setData('processed_at', $this->replicationHelper->getDateTime());
            $replDiscount->setData('processed', 1);
            $replDiscount->setData('is_updated', 0);
            // @codingStandardsIgnoreLine
            $this->replDiscountRepository->save($replDiscount);
        }
    }

    /**
     * synchronize validaton period
     */
    public function syncValidationPeriod()
    {
        $index    = false;
        $filters  = [
            ['field' => 'scope_id', 'value' => $this->getScopeId(), 'condition_type' => 'eq']
        ];
        $criteria = $this->replicationHelper->buildCriteriaForDirect(
            $filters,
            -1,
            false,
            ['field' => 'is_updated', 'value' => 1, 'condition_type' => 'eq'],
            ['field' => 'processed', 'value' => 0, 'condition_type' => 'eq'],
        );
        /** @var ReplDiscountSearchResults $replDiscounts */
        $replDiscountValidation = $this->discountValidationRepository->getList($criteria);
        /** @var ReplDiscountValidation $replValidation */
        foreach ($replDiscountValidation->getItems() as $replValidation) {
            $fromDate = $replValidation->getStartDate();
            $toDate   = $replValidation->getEndDate();
            $filters  = [
                ['field' => 'Type', 'value' => ReplDiscountType::DISC_OFFER, 'condition_type' => 'eq'],
                ['field' => 'ValidationPeriodId', 'value' => $replValidation->getNavId(), 'condition_type' => 'eq'],
                ['field' => 'Enabled', 'value' => 1, 'condition_type' => 'eq'],
                ['field' => 'scope_id', 'value' => $this->getScopeId(), 'condition_type' => 'eq']
            ];
            $criteria = $this->replicationHelper->buildCriteriaForDirect(
                $filters,
                -1,
                true
            );
            /** @var ReplDiscountSearchResults $replDiscounts */
            $replDiscounts = $this->replDiscountRepository->getList($criteria);
            /** @var ReplDiscountSetup $replDiscount * */
            foreach ($replDiscounts->getItems() as $replDiscount) {
                if ($replDiscount->getLineType() != OfferDiscountLineType::ITEM) {
                    $name = $replDiscount->getOfferNo() . '-' . $replDiscount->getLineNumber();
                } else {
                    $name = $replDiscount->getOfferNo();
                }
                $websiteIds     = [$this->store->getWebsiteId()];
                $ruleCollection = $this->ruleCollectionFactory->create();
                $ruleCollection->addFieldToFilter('name', $name);
                $ruleCollection->addFieldToFilter('website_ids', $websiteIds);
                try {
                    foreach ($ruleCollection as $rule) {
                        if ($rule->getFromDate() != $fromDate || $rule->getToDate() != $toDate) {
                            $rule->setFromDate(($fromDate) ?: $this->replicationHelper->getCurrentDate());
                            if (strtolower($toDate ?? '') != strtolower('1753-01-01T00:00:00')
                                && !empty($toDate)) {
                                $rule->setToDate($toDate);
                            }

                            $this->catalogRule->save($rule);
                            $index = true;
                        }
                    }

                } catch (Exception $e) {
                    $this->logDetailedException(__METHOD__, $this->store->getName(), $replDiscount->getOfferNo());
                    $this->logger->debug($e->getMessage());
                }
            }
            $replValidation->setData('processed_at', $this->replicationHelper->getDateTime());
            $replValidation->setData('processed', 1);
            $replValidation->setData('is_updated', 0);
            //@codingStandardsIgnoreLine
            $this->discountValidationRepository->save($replValidation);
        }

        if ($index) {
            $this->jobApply->applyAll();
        }

    }

    /**
     * Delete offer by Repl Discount Setup
     *
     * @param ReplDiscountSetup $replDiscount
     * @return void
     */
    public function deleteOfferByName(
        ReplDiscountSetup $replDiscount
    ) {
        $isItem = false;
        if ($replDiscount->getLineType() != OfferDiscountLineType::ITEM) {
            $name = $replDiscount->getOfferNo() . '-' . $replDiscount->getLineNumber();
        } else {
            $name   = $replDiscount->getOfferNo();
            $isItem = true;
        }
        $websiteIds     = [$this->store->getWebsiteId()];
        $ruleCollection = $this->ruleCollectionFactory->create();
        $ruleCollection->addFieldToFilter('name', $name);
        $ruleCollection->addFieldToFilter('website_ids', $websiteIds);
        try {
            foreach ($ruleCollection as $rule) {
                if ($isItem) {
                    $conditions      = $rule->getConditions();
                    $conditionsArray = $conditions->getConditions();
                    foreach ($conditionsArray as $condition) {
                        if ($condition->getAttribute() == 'sku') {
                            if (in_array($replDiscount->getNumber(), explode(',', $condition->getValue()))) {
                                $this->catalogRule->deleteById($rule->getId());
                            }
                        }
                    }
                } else {
                    $this->catalogRule->deleteById($rule->getId());
                }
                if ($isItem && ($replDiscount->isDeleted() == 1 || $replDiscount->getIsUpdated() == 1)) {
                    $filters  = [
                        ['field' => 'LineType', 'value' => OfferDiscountLineType::ITEM, 'condition_type' => 'eq'],
                        ['field' => 'OfferNo', 'value' => $replDiscount->getOfferNo(), 'condition_type' => 'eq'],
                        ['field' => 'Type', 'value' => ReplDiscountType::DISC_OFFER, 'condition_type' => 'eq'],
                        ['field' => 'Enabled', 'value' => 1, 'condition_type' => 'eq'],
                        ['field' => 'scope_id', 'value' => $this->getScopeId(), 'condition_type' => 'eq']
                    ];
                    $criteria = $this->replicationHelper->buildCriteriaForDirect(
                        $filters,
                        -1,
                        true);
                    /** @var ReplDiscountSearchResults $replDiscountsOffer */
                    $replDiscountsOffer = $this->replDiscountRepository->getList($criteria);
                    /** @var ReplDiscountSetup $replDiscountOffer */
                    foreach ($replDiscountsOffer->getItems() as $replDiscountsOffer) {
                        $replDiscountsOffer->setData('setUpdatedAt', $this->replicationHelper->getDateTime());
                        $replDiscountsOffer->setData('is_updated', 1);
                        // @codingStandardsIgnoreLine
                        $this->replDiscountRepository->save($replDiscountsOffer);
                    }
                }
            }
        } catch (Exception $e) {
            $this->logDetailedException(__METHOD__, $this->store->getName(), $replDiscount->getOfferNo());
            $this->logger->debug($e->getMessage());
        }
    }

    /**
     * Get remaining records
     *
     * @param string $storeId
     * @return int
     * @throws NoSuchEntityException
     */
    public function getRemainingRecords(
        $storeId
    ) {
        if (!$this->remainingRecords) {
            $filtersStatus          = [
                ['field' => 'scope_id', 'value' => $storeId, 'condition_type' => 'eq'],
                ['field' => 'Type', 'value' => ReplDiscountType::DISC_OFFER, 'condition_type' => 'eq'],
                ['field' => 'Enabled', 'value' => 1, 'condition_type' => 'eq']
            ];
            $criteriaTotal          = $this->replicationHelper->buildCriteriaForArray($filtersStatus, 2, 1);
            $this->remainingRecords = $this->replDiscountRepository->getList($criteriaTotal)
                ->getTotalCount();
        }
        return $this->remainingRecords;
    }

    /**
     * Log Detailed exception
     *
     * @param string $method
     * @param string $storeName
     * @param string $itemId
     * @return void
     */
    public function logDetailedException(
        $method, $storeName, $itemId
    ) {
        $this->logger->debug(
            sprintf(
                'Exception happened in %s for store %s, item id: %s',
                $method,
                $storeName,
                $itemId
            )
        );
    }

    /**
     * Get conditions based on line type
     *
     * @param mixed $key
     * @return array
     */
    public function getConditions(
        $key
    ) {
        $lineType = '';
        $number   = '';

        if ($key instanceof ReplDiscountSetup) {
            $lineType = $key->getLineType();
            $number   = $key->getNumber();
        }

        if (is_array($key)) {
            $lineType = OfferDiscountLineType::ITEM;
        }

        // Create root conditions to match with all child conditions
        $conditions['1'] =
            [
                'type'       => Combine::class,
                'aggregator' => 'all',
                'value'      => 1,
                'new_child'  => ''
            ];
        if ($lineType == OfferDiscountLineType::ITEM_CATEGORY) {
            $conditions['1--1'] =
                [
                    'type'      => Product::class,
                    'attribute' => LSR::LS_ITEM_CATEGORY,
                    'operator'  => '==',
                    'value'     => $number
                ];

        } elseif ($lineType == OfferDiscountLineType::PRODUCT_GROUP) {

            $conditions['1--1'] =
                [
                    'type'      => Product::class,
                    'attribute' => LSR::LS_ITEM_PRODUCT_GROUP,
                    'operator'  => '==',
                    'value'     => $number
                ];

        } elseif ($lineType == OfferDiscountLineType::ITEM) {
            $conditions['1--1'] =
                [
                    'type'      => Product::class,
                    'attribute' => 'sku',
                    'operator'  => '()',
                    'value'     => implode(',', $key)
                ];
        }

        return $conditions;
    }

    /**
     * Get current scope id
     *
     * @return int
     */
    public function getScopeId()
    {
        return $this->store->getWebsiteId();
    }
}
