<?php

namespace Ls\Replication\Cron;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\DiscountValueType;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\OfferDiscountLineType;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\ReplDiscMemberType;
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
use \Ls\Replication\Model\ResourceModel\ReplStore\Collection as StoreCollection;
use \Ls\Replication\Model\ResourceModel\ReplStore\CollectionFactory as StoreCollectionFactory;
use Magento\CatalogRule\Api\CatalogRuleRepositoryInterface;
use Magento\CatalogRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use Magento\CatalogRule\Model\Rule\Condition\Combine;
use Magento\CatalogRule\Model\Rule\Condition\Product;
use Magento\CatalogRule\Model\Rule\Job;
use Magento\CatalogRule\Model\RuleFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InvalidTransitionException;
use Magento\Store\Api\Data\StoreInterface;
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
     * @var StoreCollection
     */
    public $storeCollection;

    /**
     * @var StoreCollectionFactory
     */
    public $storeCollectionFactory;

    /**
     * @var string
     */
    public $message;

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
     * @param StoreCollectionFactory $storeCollectionFactory
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
        StoreCollectionFactory $storeCollectionFactory,
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
        $this->storeCollectionFactory       = $storeCollectionFactory;
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
        if (!$this->lsr->isSSM()) {
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

                    $fullReplicationDiscountValidationStatus    = $this->lsr->getConfigValueFromDb(
                        ReplEcommDiscountValidationsTask::CONFIG_PATH_STATUS,
                        ScopeInterface::SCOPE_WEBSITES,
                        $this->getScopeId()
                    );
                    $fullReplicationItemVariantRegistrationTask = $this->lsr->getConfigValueFromDb(
                        ReplEcommItemVariantRegistrationsTask::CONFIG_PATH_STATUS,
                        ScopeInterface::SCOPE_WEBSITES,
                        $this->getScopeId()
                    );

                    $fullReplicationProductCreateTask = $this->lsr->getConfigValueFromDb(
                        LSR::SC_SUCCESS_CRON_PRODUCT,
                        ScopeInterface::SCOPE_STORES,
                        $store->getId()
                    );

                    if ($fullReplicationDiscountValidationStatus && $fullReplicationItemVariantRegistrationTask
                        && $fullReplicationProductCreateTask) {
                        $this->logger->debug('Running DiscountCreateTask for store ' . $this->store->getName());
                        $this->replicationHelper->updateConfigValue(
                            $this->replicationHelper->getDateTime(),
                            LSR::SC_CRON_DISCOUNT_CONFIG_PATH_LAST_EXECUTE_SETUP,
                            $this->store->getId(),
                            ScopeInterface::SCOPE_STORES
                        );
                        /* Delete the IsDeleted offers */
                        $this->deleteOffers();
                        $storeId                  = $this->getScopeId();
                        $publishedOfferCollection = $this->getUniquePublishedOffers($storeId);
                        if (!empty($publishedOfferCollection)) {
                            $reindexRules = false;
                            $schemes      = $this->contactHelper->getSchemes();
                            $this->createAllAvailableCustomerGroups($schemes);
                            /** @var ReplDiscountSetup $item */
                            foreach ($publishedOfferCollection as $item) {
                                $this->deleteOfferByName($item);
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
                                    try {
                                        $this->deleteOfferItemCategoryProductGroup($replDiscount);
                                        if (!$replDiscount->getIsPercentage()) {
                                            $discountValue     = $replDiscount->getLineDiscountAmountInclVAT();
                                        } else {
                                            $discountValue     = $replDiscount->getDealPriceDiscount();
                                        }
                                        if (empty($customerGroupIds) && !$useAllGroupIds) {
                                            $customerGroupIds =
                                                $this->getRequiredCustomerGroups($replDiscount, $schemes);
                                        }

                                        $lineType = (string)$replDiscount->getLineType();

                                        if ($lineType == OfferDiscountLineType::ITEM) {
                                            $this->getItemsInRequiredFormat($replDiscount, $skuAmountArray);
                                        } else {
                                            $categoryGroupAmountArray[$discountValue][] = $replDiscount;
                                        }
                                        $replDiscount->setData('is_failed', 0);
                                    } catch (Exception $e) {
                                        $this->logger->debug(
                                            sprintf(
                                                'Exception happened in %s for store: %s, item id: %s, variant id: %s',
                                                __METHOD__,
                                                $this->store->getName(),
                                                $replDiscount->getNumber(),
                                                $replDiscount->getVariantId()
                                            )
                                        );
                                        $this->logger->debug($e->getMessage());
                                        $replDiscount->setData('is_failed', 1);
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
                        /* Synchronize validation period */
                        $this->syncValidationPeriod();
                        $this->logger->debug('End DiscountCreateTask for store ' . $this->store->getName());
                    } else {
                        $this->message = __('repl_discount_validation, repl_item_variant_registration from scope website level should run first.
                        Discounts will be replicated once repl_products gets completed.');
                        $this->logger->debug($this->message);
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
        $this->message = '';
        $this->execute($storeData);
        if (!empty($this->message)) {
            return [$this->message];
        }
        $discountsLeftToProcess = $this->getRemainingRecords($storeData->getId());
        return [$discountsLeftToProcess];
    }

    /**
     * Gather all items together with respective discount values
     *
     * @param $replDiscount
     * @param $skuAmountArray
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getItemsInRequiredFormat($replDiscount, &$skuAmountArray)
    {
        $storeId = $this->getScopeId();

        if (!$replDiscount->getIsPercentage()) {
            $discountValueType = DiscountValueType::AMOUNT;
            $discountValue     = $replDiscount->getLineDiscountAmountInclVAT();
        } else {
            $discountValueType = DiscountValueType::PERCENT;
            $discountValue     = $replDiscount->getDealPriceDiscount();
        }

        $uomCodes[$replDiscount->getNumber()][] = '';
        $variantIds                             = null;
        $uomCodes                               = $this->replicationHelper->getUomCodes(
            $replDiscount->getNumber(),
            $storeId
        );

        if (empty($uomCodes)) {
            $uomCodes[$replDiscount->getNumber()] [] = '';
        }

        if (!empty($uomCodes[$replDiscount->getNumber()])) {
            if (count($uomCodes[$replDiscount->getNumber()]) > 1 &&
                !empty($replDiscount->getUnitOfMeasureId())) {
                $uomCodes                             = [];
                $uomCodes[$replDiscount->getNumber()]
                [$replDiscount->getUnitOfMeasureId()] =
                    $replDiscount->getUnitOfMeasureId();
            } elseif (count($uomCodes[$replDiscount->getNumber()]) == 1) {
                $uomCodes                               = [];
                $uomCodes[$replDiscount->getNumber()][] = '';
            }
        } else {
            $uomCodes[$replDiscount->getNumber()] [] = '';
        }

        if (empty($replDiscount->getVariantId())) {
            foreach ($uomCodes[$replDiscount->getNumber()] as $uomCode) {
                $products = $this->replicationHelper->
                getProductDataByIdentificationAttributes(
                    $replDiscount->getNumber(),
                    $replDiscount->getVariantId(),
                    $uomCode,
                    $this->store->getId(),
                    false,
                    true
                );
                foreach ($products as $product) {
                    $skuAmountArray[$discountValue][$discountValueType] []
                        = $product->getSku();
                }
            }
        } elseif (!empty($replDiscount->getVariantId())) {
            if ($replDiscount->getVariantType() == 2) {
                $variantIds = $this->getVariantIdsByDimension(
                    $replDiscount->getNumber(),
                    $replDiscount->getVariantId(),
                    $storeId
                );
                if (empty($variantIds)) {
                    throw new NoSuchEntityException();
                }
            } else {
                $variantIds[] = $replDiscount->getVariantId();
            }
            foreach ($variantIds as $variantId) {
                foreach ($uomCodes[$replDiscount->getNumber()] as $uomCode) {
                    $products = $this->replicationHelper->
                    getProductDataByIdentificationAttributes(
                        $replDiscount->getNumber(),
                        $variantId,
                        $uomCode,
                        $this->store->getId(),
                        false,
                        true
                    );
                    foreach ($products as $product) {
                        $skuAmountArray[$discountValue][$discountValueType] []
                            = $product->getSku();
                    }
                }
            }
        }

        return $skuAmountArray;
    }

    /**
     * Get required customer groups
     *
     * @param $replDiscount
     * @param $schemes
     * @return array
     * @throws InputException
     * @throws InvalidTransitionException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getRequiredCustomerGroups($replDiscount, $schemes)
    {
        $customerGroupIds = [];
        if ($replDiscount->getMemberType() == ReplDiscMemberType::CLUB
            && !empty($replDiscount->getLoyaltySchemeCode()) && !empty($schemes)) {
            $groups = array_keys($schemes, $replDiscount->getLoyaltySchemeCode());
            foreach ($groups as $group) {
                $customerGroupIds[] = $this->contactHelper->
                getCustomerGroupIdByName($group);
            }
        } else {
            $customerGroupIds[] = $this->contactHelper->getCustomerGroupIdByName(
                $replDiscount->getLoyaltySchemeCode()
            );
        }

        return $customerGroupIds;
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
        $websiteId  = $replDiscount->getScopeId();
        if (version_compare(
            $this->lsr->getOmniVersion($websiteId, ScopeInterface::SCOPE_WEBSITES),
            '2024.10.0',
            '<='
        ) || $this->validateWebsiteByStoreGroupCodeOrPriceGroup(
            $replDiscount->getPriceGroup(),
            $replDiscount->getStoreGroupCodes(),
            $replDiscount->getScopeId(),
            $replDiscount->getOfferNo()
        )) {
            if ($amount == null) {
                $amount = $replDiscount->getDiscountValue();
            }

            if ($key instanceof ReplDiscountSetup) {
                $name              = $key->getOfferNo() . '-' . $key->getLineNumber();
                $discountValueType = (!$key->getIsPercentage()) ? DiscountValueType::AMOUNT :
                    DiscountValueType::PERCENT;
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
                ->setWebsiteIds($websiteId)
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

        $collection->addFieldToFilter(
            'IsDeleted',
            0
        );

        $collection->addFieldToFilter(
            ['processed', 'is_updated'],
            [
                ['eq' => 0],
                ['eq' => 1]
            ]
        );
        $query = $collection->getSelect()->__toString();
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
                $ruleCollection = $this->getCatalogRuleCollection($name);

                try {
                    foreach ($ruleCollection as $rule) {
                        $index = $this->saveCatalogRuleBasedOnDiscountValidation($rule, $replValidation);
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

    public function getCatalogRuleCollection($name)
    {
        $websiteIds     = [$this->store->getWebsiteId()];
        $ruleCollection = $this->ruleCollectionFactory->create();
        $ruleCollection->addFieldToFilter('name', $name);
        $ruleCollection->addFieldToFilter('website_ids', $websiteIds);

        return $ruleCollection;
    }

    /**
     * Save catalog rule
     *
     * @param $rule
     * @param $replValidation
     * @return boolean
     * @throws CouldNotSaveException
     */
    public function saveCatalogRuleBasedOnDiscountValidation($rule, $replValidation)
    {
        $fromDate = $replValidation->getStartDate();
        $toDate   = $replValidation->getEndDate();

        if ($rule->getFromDate() != $fromDate || $rule->getToDate() != $toDate) {
            $rule->setFromDate(($fromDate) ?: $this->replicationHelper->getCurrentDate());
            if (strtolower($toDate ?? '') != strtolower('1753-01-01T00:00:00')
                && !empty($toDate)) {
                $rule->setToDate($toDate);
            }

            $this->catalogRule->save($rule);
            return true;
        }

        return false;
    }

    /**
     * Delete offer of product group, item category and special group by Repl Discount Setup
     *
     * @param ReplDiscountSetup $replDiscount
     * @return void
     */
    public function deleteOfferItemCategoryProductGroup(
        ReplDiscountSetup $replDiscount
    ) {
        $name = '';
        if ($replDiscount->getLineType() != OfferDiscountLineType::ITEM) {
            $name = $replDiscount->getOfferNo() . '-' . $replDiscount->getLineNumber();
        }
        if (!empty($name)) {
            $websiteIds     = [$this->store->getWebsiteId()];
            $ruleCollection = $this->ruleCollectionFactory->create();
            $ruleCollection->addFieldToFilter('name', $name);
            $ruleCollection->addFieldToFilter('website_ids', $websiteIds);
            try {
                foreach ($ruleCollection as $rule) {
                    $this->catalogRule->deleteById($rule->getId());
                }
            } catch (Exception $e) {
                $this->logDetailedException(__METHOD__, $this->store->getName(), $replDiscount->getOfferNo());
                $this->logger->debug($e->getMessage());
            }
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
                $this->catalogRule->deleteById($rule->getId());
                if ($isItem) {
                    $filters  = [
                        ['field' => 'LineType', 'value' => OfferDiscountLineType::ITEM, 'condition_type' => 'eq'],
                        ['field' => 'OfferNo', 'value' => $replDiscount->getOfferNo(), 'condition_type' => 'eq'],
                        ['field' => 'Enabled', 'value' => 1, 'condition_type' => 'eq'],
                        ['field' => 'scope_id', 'value' => $this->getScopeId(), 'condition_type' => 'eq']
                    ];
                    $criteria = $this->replicationHelper->buildCriteriaForDirect(
                        $filters,
                        -1,
                        true
                    );
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
        $method,
        $storeName,
        $itemId
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
        $conditions['1']
            = [
            'type'       => Combine::class,
            'aggregator' => 'all',
            'value'      => 1,
            'new_child'  => ''
        ];
        if ($lineType == OfferDiscountLineType::ITEM_CATEGORY) {
            $conditions['1--1']
                = [
                'type'      => Product::class,
                'attribute' => LSR::LS_ITEM_CATEGORY,
                'operator'  => '==',
                'value'     => $number
            ];

        } elseif ($lineType == OfferDiscountLineType::PRODUCT_GROUP) {

            $conditions['1--1']
                = [
                'type'      => Product::class,
                'attribute' => LSR::LS_ITEM_PRODUCT_GROUP,
                'operator'  => '==',
                'value'     => $number
            ];

        } elseif ($lineType == OfferDiscountLineType::SPECIAL_GROUP) {

            $conditions['1--1']
                = [
                'type'      => Product::class,
                'attribute' => LSR::LS_ITEM_SPECIAL_GROUP,
                'operator'  => '{}',
                'value'     => $number . ';'
            ];

        } elseif ($lineType == OfferDiscountLineType::ITEM) {
            $conditions['1--1']
                = [
                'type'      => Product::class,
                'attribute' => 'sku',
                'operator'  => '()',
                'value'     => implode(',', $key)
            ];
        }

        return $conditions;
    }

    /**
     * Get Variant Ids by Dimension
     *
     * @param $itemId
     * @param $dimension
     * @param $storeId
     * @return null
     */
    public function getVariantIdsByDimension(
        $itemId,
        $dimension,
        $storeId
    ) {
        return $this->replicationHelper->getVariantIdsByDimension($itemId, $dimension, $storeId);
    }

    /**
     * Check if website exist in store group or price group
     *
     * @param $priceGroup
     * @param $storeGroup
     * @param $websiteId
     * @param $offerNo
     * @return bool
     */
    public function validateWebsiteByStoreGroupCodeOrPriceGroup(
        $priceGroup,
        $storeGroup,
        $websiteId,
        $offerNo
    ) {
        $webStore      = $this->lsr->getWebsiteConfig(LSR::SC_SERVICE_STORE, $websiteId);
        $storeGroups   = $priceGroups = $storeGroupCodes = [];
        $webStoreGroup = $webStorePriceGroup = '';
        if ($webStore) {
            if ($storeGroup) {
                $storeGroupCodes = array_filter(explode(';', $storeGroup));
            }
            $storesData = $this->storeCollectionFactory->create()
                ->addFieldToFilter(
                    'scope_id',
                    $websiteId
                )->addFieldToFilter('nav_id', $webStore);
            foreach ($storesData->getItems() as $storeData) {
                if ($storeData->getStoreGroupCodes()) {
                    $webStoreGroup = $storeData->getStoreGroupCodes();
                    $storeGroups   = array_filter(explode(';', $webStoreGroup));
                }
                if ($storeData->getPriceGroupCodes()) {
                    $webStorePriceGroup = $storeData->getPriceGroupCodes();
                    $priceGroups        = array_filter(explode(';', $webStorePriceGroup));
                }
            }

            if (empty($webStoreGroup) && empty($webStorePriceGroup)) {
                $this->logger->debug(
                    sprintf(
                        'The store group or price group for web store %s in replication store is empty. Reset and execute the replication store cron job to get the required values',
                        $webStore
                    )
                );
            }
        }

        if (!empty($storeGroups) || !empty($priceGroups)) {
            $resultArray = array_intersect($storeGroupCodes, $storeGroups);
            if ($resultArray || in_array($priceGroup, $priceGroups)) {
                return true;
            } else {
                $this->logger->debug(
                    sprintf(
                        'The store group %s or price group %s set for discount offer %s is different than the store group %s or price group %s for web store %s in central',
                        $storeGroup,
                        $priceGroup,
                        $offerNo,
                        $webStoreGroup,
                        $webStorePriceGroup,
                        $webStore
                    )
                );
            }
        }

        return false;
    }

    /**
     * Create all available customer groups
     *
     * @param $schemes
     * @return void
     * @throws InputException
     * @throws InvalidTransitionException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function createAllAvailableCustomerGroups($schemes)
    {
        $customGroups = array_keys($schemes);

        foreach ($customGroups as $customGroup) {
            $this->contactHelper->getCustomerGroupIdByName($customGroup);
        }
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
