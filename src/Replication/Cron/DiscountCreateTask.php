<?php

namespace Ls\Replication\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Omni\Helper\ContactHelper;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\ReplDiscountType;
use Magento\CatalogRule\Api\CatalogRuleRepositoryInterface;
use Magento\CatalogRule\Model\RuleFactory;
use Magento\CatalogRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use Magento\CatalogRule\Model\Rule\Job;
use \Ls\Replication\Model\ResourceModel\ReplDiscount\CollectionFactory;
use \Ls\Replication\Api\ReplDiscountRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * This cron will create catalog rules in order to integrate the pre-active
 * discounts which are independent of any Member Limitation
 * One Sales Rule  = All discounts based on Published Offer.
 * Condition will be to have any of the value equal to the SKUs found in it.
 * Priority will be same for published offer as it was created in Nav.
 *
 * Class DiscountCreateTask
 * @package Ls\Replication\Cron
 */
class DiscountCreateTask
{

    const CONFIG_PATH_LAST_EXECUTE = 'ls_mag/replication/last_execute_repl_discount_create';

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
     * @var ReplDiscountRepositoryInterface
     */
    public $replDiscountRepository;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * @var LoggerInterface
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

    /**
     * DiscountCreateTask constructor.
     * @param CatalogRuleRepositoryInterface $catalogRule
     * @param RuleFactory $ruleFactory
     * @param RuleCollectionFactory $ruleCollectionFactory
     * @param Job $jobApply
     * @param ReplDiscountRepositoryInterface $replDiscountRepository
     * @param ReplicationHelper $replicationHelper
     * @param LSR $LSR
     * @param CollectionFactory $replDiscountCollection
     * @param ContactHelper $contactHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        CatalogRuleRepositoryInterface $catalogRule,
        RuleFactory $ruleFactory,
        RuleCollectionFactory $ruleCollectionFactory,
        Job $jobApply,
        ReplDiscountRepositoryInterface $replDiscountRepository,
        ReplicationHelper $replicationHelper,
        LSR $LSR,
        CollectionFactory $replDiscountCollection,
        ContactHelper $contactHelper,
        LoggerInterface $logger
    ) {
        $this->catalogRule = $catalogRule;
        $this->ruleFactory = $ruleFactory;
        $this->ruleCollectionFactory = $ruleCollectionFactory;
        $this->jobApply = $jobApply;
        $this->replDiscountRepository = $replDiscountRepository;
        $this->replicationHelper = $replicationHelper;
        $this->contactHelper = $contactHelper;
        $this->lsr = $LSR;
        $this->replDiscountCollection = $replDiscountCollection;
        $this->logger = $logger;
    }

    /**
     * Discount Creation
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\State\InvalidTransitionException
     */
    public function execute($storeData = null)
    {
        /**
         * Get all Unique Publish offer so that we can create catalog rules based on that.
         * Only gonna work if everything is good to go.
         * And the web store is being set in the Magento.
         * And we need to apply only those rules which are associated to the store assigned to it.
         */
        if (!empty($storeData)) {
            $stores = [$storeData];
        } else {
            /** @var \Magento\Store\Api\Data\StoreInterface[] $stores */
            $stores = $this->lsr->getAllStores();
        }
        if (!empty($stores)) {
            foreach ($stores as $store) {
                //setting the store id globally.
                $this->lsr->setStoreId($store->getId());
                $this->store = $store;
                //adding is_lsr check to avoid wasting time for the stores which is not setup
                if ($this->lsr->isLSR($this->store->getId())) {
                    $this->replicationHelper->updateConfigValue(date('d M,Y h:i:s A'), self::CONFIG_PATH_LAST_EXECUTE);
                    $cronProductCheck = $this->lsr->getStoreConfig(LSR::SC_SUCCESS_CRON_PRODUCT, $store->getId());
                    if ($cronProductCheck == 1) {
                        $publishedOfferCollection = $this->getUniquePublishedOffers($store->getId());
                        if (!empty($publishedOfferCollection)) {
                            $reindexRules = false;
                            /** @var \Ls\Replication\Model\ReplDiscount $item */
                            foreach ($publishedOfferCollection as $item) {
                                $filters = [
                                    ['field' => 'scope_id', 'value' => $this->store->getId(), 'condition_type' => 'eq'],
                                    ['field' => 'OfferNo', 'value' => $item->getOfferNo(), 'condition_type' => 'eq'],
                                    [
                                        'field' => 'Type',
                                        'value' => ReplDiscountType::DISC_OFFER,
                                        'condition_type' => 'eq'
                                    ]
                                ];

                                $criteria = $this->replicationHelper->buildCriteriaForArray($filters, 100);
                                /** @var \Ls\Replication\Model\ReplDiscountSearchResults $replDiscounts */
                                $replDiscounts = $this->replDiscountRepository->getList($criteria);
                                $skuArray = [];
                                if ($item->getLoyaltySchemeCode() == '' ||
                                    $item->getLoyaltySchemeCode() == null
                                ) {
                                    $useAllGroupIds = true;
                                    $customerGroupIds = $this->contactHelper->getAllCustomerGroupIds();
                                } else {
                                    $useAllGroupIds = false;
                                    $customerGroupIds = [];
                                }
                                if ($replDiscounts->getItems()) {
                                    /** We check if offer exist */
                                    $deleteStatus = $this->deleteOfferByName($item->getOfferNo());
                                    if ($deleteStatus) {
                                        $criteriaAfterDelete = $this->replicationHelper->buildCriteriaForArray($filters,
                                            100);
                                        /** @var \Ls\Replication\Model\ReplDiscountSearchResults $replDiscounts */
                                        $replDiscounts = $this->replDiscountRepository->getList($criteriaAfterDelete);
                                    }
                                }
                                /** @var \Ls\Replication\Model\ReplDiscount $replDiscount */
                                foreach ($replDiscounts->getItems() as $replDiscount) {
                                    $customerGroupId = $this->contactHelper->getCustomerGroupIdByName(
                                        $replDiscount->getLoyaltySchemeCode()
                                    );
                                    // To check if discounts groups are specific for any Member Scheme.
                                    if (!$useAllGroupIds && !in_array($customerGroupId, $customerGroupIds)) {
                                        $customerGroupIds[] = $this->contactHelper->getCustomerGroupIdByName(
                                            $replDiscount->getLoyaltySchemeCode()
                                        );
                                    }
                                    if ($replDiscount->getVariantId() == '' ||
                                        $replDiscount->getVariantId() == null
                                    ) {
                                        $skuArray[] = $replDiscount->getItemId();
                                    } else {
                                        $skuArray[] = $replDiscount->getItemId() . '-' . $replDiscount->getVariantId();
                                    }
                                    $replDiscount->setData('processed', '1');
                                    $replDiscount->setData('is_updated', '0');
                                    // @codingStandardsIgnoreStart
                                    $this->replDiscountRepository->save($replDiscount);
                                    // @codingStandardsIgnoreEnd
                                }
                                if (!empty($skuArray)) {
                                    $skuArray = array_unique($skuArray);
                                    $this->addSalesRule($item, $skuArray, $customerGroupIds, $store->getWebsiteId());
                                    $reindexRules = true;
                                }
                            }
                            if ($reindexRules) {
                                $this->jobApply->applyAll();
                            }
                            $filtersStatus = [
                                ['field' => 'Type', 'value' => ReplDiscountType::DISC_OFFER, 'condition_type' => 'eq']
                            ];
                            $criteriaTotal = $this->replicationHelper->buildCriteriaForArray($filtersStatus, 100);
                            /** @var \Ls\Replication\Model\ReplDiscountSearchResults $replDiscounts */
                            $replDiscountsTotal = $this->replDiscountRepository->getList($criteriaTotal);
                            if (count($replDiscountsTotal->getItems()) == 0) {
                                $this->replicationHelper->updateCronStatus(true, LSR::SC_SUCCESS_CRON_DISCOUNT);
                            } else {
                                $this->replicationHelper->updateCronStatus(false, LSR::SC_SUCCESS_CRON_DISCOUNT);
                            }
                        }
                        /* Delete the IsDeleted offers */
                        $this->deleteOffers();
                    } else {
                        $this->logger->debug('Discount Replication cron fails because product replication cron not executed 
                successfully.');
                    }
                }
                // unsetting the store id.
                $this->lsr->setStoreId(null);
            }
        }
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\State\InvalidTransitionException
     */
    public function executeManually($storeData = null)
    {
        $this->execute($storeData);
        $filters = [
            ['field' => 'scope_id', 'value' => $storeData->getId(), 'condition_type' => 'eq'],
            [
                'field' => 'Type',
                'value' => ReplDiscountType::DISC_OFFER,
                'condition_type' => 'eq'
            ]
        ];
        $criteria = $this->replicationHelper->buildCriteriaForArray($filters, -1);
        $items = $this->replDiscountRepository->getList($criteria);
        $itemsLeftToProcess = count($items->getItems());
        return [$itemsLeftToProcess];
    }

    /**
     * @param \Ls\Replication\Model\ReplDiscount $replDiscount
     * @param array $skuArray
     * @param $customerGroupIds
     * @param $websiteId
     */
    public function addSalesRule(\Ls\Replication\Model\ReplDiscount $replDiscount, array $skuArray, $customerGroupIds, $websiteId)
    {
        if ($replDiscount instanceof \Ls\Replication\Model\ReplDiscount) {
            $websiteIds = [$websiteId];
            $rule = $this->ruleFactory->create();
            // Create root conditions to match with all child conditions
            $conditions["1"] =
                [
                    "type" => "Magento\CatalogRule\Model\Rule\Condition\Combine",
                    "aggregator" => "all",
                    "value" => 1,
                    "new_child" => ""
                ];

            $conditions["1--1"] =
                [
                    "type" => "Magento\CatalogRule\Model\Rule\Condition\Product",
                    "attribute" => "sku",
                    "operator" => "()",
                    "value" => implode(',', $skuArray)
                ];

            $rule->setName($replDiscount->getOfferNo())
                ->setDescription($replDiscount->getDescription())
                ->setIsActive(1)
                ->setCustomerGroupIds($customerGroupIds)
                ->setWebsiteIds($websiteIds)
                ->setFromDate($replDiscount->getFromDate());

            // Discounts for aspecific time.
            if (strtolower($replDiscount->getToDate()) != strtolower('1753-01-01T00:00:00')) {
                $rule->setToDate($replDiscount->getToDate());
            }
            if ($replDiscount->getDiscountValueType() == 'Amount') {
                $type = 'by_fixed';
            } else {
                $type = 'by_percent';
            }
            $rule->setSimpleAction($type)
                ->setDiscountAmount($replDiscount->getDiscountValue())
                ->setStopRulesProcessing(1)
                ->setSortOrder($replDiscount->getPriorityNo());

            /**
             * Default Values for Action Types.
             * by_percent
             * by_fixed
             * to_percent
             * to_fixed
             */
            $rule->setData('conditions', $conditions);
            // @codingStandardsIgnoreStart
            $validateResult = $rule->validateData(new \Magento\Framework\DataObject($rule->getData()));
            // @codingStandardsIgnoreEnd
            if ($validateResult !== true) {
                foreach ($validateResult as $errorMessage) {
                    $this->logger->debug($errorMessage);
                }
                return;
            }
            try {
                $rule->loadPost($rule->getData());
                $this->catalogRule->save($rule);
            } catch (\Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }
    }

    /**
     * @param $storeId
     * @return array|\Ls\Replication\Model\ResourceModel\ReplDiscount\Collection
     */
    public function getUniquePublishedOffers($storeId)
    {
        $publishedOfferIds = [];
        /** @var  \Ls\Replication\Model\ResourceModel\ReplDiscount\Collection $collection */
        $collection = $this->replDiscountCollection->create();
        $collection->addFieldToFilter('scope_id', $storeId);
        $collection->getSelect()
            ->columns('OfferNo')
            ->group('OfferNo');
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
        $filters = [
            ['field' => 'Type', 'value' => ReplDiscountType::DISC_OFFER, 'condition_type' => 'eq'],
            ['field' => 'scope_id', 'value' => $this->store->getId(), 'condition_type' => 'eq']
        ];
        $criteria = $this->replicationHelper->buildCriteriaGetDeletedOnly($filters);
        /** @var \Ls\Replication\Model\ReplDiscountSearchResults $replDiscounts */
        $replDiscounts = $this->replDiscountRepository->getList($criteria);
        /** @var \Ls\Replication\Model\ReplDiscount $replDiscount */
        foreach ($replDiscounts->getItems() as $replDiscount) {
            /** @var RuleCollectionFactory $ruleCollection */
            $ruleCollection = $this->ruleCollectionFactory->create();
            $ruleCollection->addFieldToFilter('name', $replDiscount->getOfferNo());
            try {
                foreach ($ruleCollection as $rule) {
                    $this->catalogRule->deleteById($rule->getId());
                }
                $replDiscount->setData('processed', '1');
                // @codingStandardsIgnoreStart
                $this->replDiscountRepository->save($replDiscount);
                // @codingStandardsIgnoreEnd
            } catch (\Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }
    }

    /**
     * Delete offer by name
     * @param $name
     * @return bool
     */
    public function deleteOfferByName($name)
    {
        $websiteIds = [$this->store->getWebsiteId()];
        $ruleCollection = $this->ruleCollectionFactory->create();
        $ruleCollection->addFieldToFilter('name', $name);
        $ruleCollection->addFieldToFilter('website_ids', $websiteIds);
        try {
            foreach ($ruleCollection as $rule) {
                $this->catalogRule->deleteById($rule->getId());
                $filters = [
                    ['field' => 'OfferNo', 'value' => $name, 'condition_type' => 'eq'],
                    ['field' => 'scope_id', 'value' => $this->store->getId(), 'condition_type' => 'eq'],
                    ['field' => 'Type', 'value' => ReplDiscountType::DISC_OFFER, 'condition_type' => 'eq']
                ];
                $criteria = $this->replicationHelper->buildCriteriaForDirect($filters, 100);
                /** @var \Ls\Replication\Model\ReplDiscountSearchResults $replDiscounts */
                $replDiscounts = $this->replDiscountRepository->getList($criteria);
                /** @var \Ls\Replication\Model\ReplDiscount $replDiscount */
                foreach ($replDiscounts->getItems() as $replDiscount) {
                    $replDiscount->setData('processed', '0');
                    $replDiscount->setData('is_updated', '0');
                    // @codingStandardsIgnoreStart
                    $this->replDiscountRepository->save($replDiscount);
                    // @codingStandardsIgnoreEnd
                }
                return true;
            }
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
        }
        return false;
    }
}
