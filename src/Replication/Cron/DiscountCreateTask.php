<?php

namespace Ls\Replication\Cron;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\ReplDiscountType;
use \Ls\Omni\Helper\ContactHelper;
use \Ls\Replication\Api\ReplDiscountRepositoryInterface;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Logger\Logger;
use \Ls\Replication\Model\ReplDiscount;
use \Ls\Replication\Model\ReplDiscountSearchResults;
use \Ls\Replication\Model\ResourceModel\ReplDiscount\Collection;
use \Ls\Replication\Model\ResourceModel\ReplDiscount\CollectionFactory;
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
use Magento\Store\Model\ScopeInterface;

/**
 * This cron will create catalog rules in order to integrate the pre-active
 * discounts which are independent of any Member Limitation
 * One Sales Rule  = All discounts based on Published Offer.
 * Condition will be to have any of the value equal to the SKUs found in it.
 * Priority will be same for published offer as it was created in Nav.
 */
class DiscountCreateTask
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
     * @var ReplDiscountRepositoryInterface
     */
    public $replDiscountRepository;

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
     * @param ReplDiscountRepositoryInterface $replDiscountRepository
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
        ReplDiscountRepositoryInterface $replDiscountRepository,
        ReplicationHelper $replicationHelper,
        LSR $LSR,
        CollectionFactory $replDiscountCollection,
        ContactHelper $contactHelper,
        Logger $logger
    ) {
        $this->catalogRule            = $catalogRule;
        $this->ruleFactory            = $ruleFactory;
        $this->ruleCollectionFactory  = $ruleCollectionFactory;
        $this->jobApply               = $jobApply;
        $this->replDiscountRepository = $replDiscountRepository;
        $this->replicationHelper      = $replicationHelper;
        $this->contactHelper          = $contactHelper;
        $this->lsr                    = $LSR;
        $this->replDiscountCollection = $replDiscountCollection;
        $this->logger                 = $logger;
    }

    /**
     * Discount Creation
     * @param null $storeData
     * @throws InputException
     * @throws InvalidTransitionException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Exception
     */
    public function execute($storeData = null)
    {
        /**
         * Get all Unique Publish offer so that we can create catalog rules based on that.
         * And the web store is being set in the Magento.
         * And we need to apply only those rules which are associated to the store assigned to it.
         */
        if (!empty($storeData) && $storeData instanceof StoreInterface) {
            $stores = [$storeData];
        } else {
            /** @var StoreInterface[] $stores */
            $stores = $this->lsr->getAllStores();
        }
        if (!empty($stores)) {
            foreach ($stores as $store) {
                $this->lsr->setStoreId($store->getId());
                $this->store = $store;
                if ($this->lsr->isLSR($this->store->getId())) {
                    $this->logger->debug('Running DiscountCreateTask for store ' . $this->store->getName());
                    $this->replicationHelper->updateConfigValue(
                        $this->replicationHelper->getDateTime(),
                        LSR::SC_CRON_DISCOUNT_CONFIG_PATH_LAST_EXECUTE,
                        $this->store->getId(),
                        ScopeInterface::SCOPE_STORES
                    );
                    $storeId                  = $this->getScopeId();
                    $publishedOfferCollection = $this->getUniquePublishedOffers($storeId);
                    if (!empty($publishedOfferCollection)) {
                        $reindexRules = false;
                        /** @var ReplDiscount $item */
                        foreach ($publishedOfferCollection as $item) {
                            $filters = [
                                ['field' => 'scope_id', 'value' => $storeId, 'condition_type' => 'eq'],
                                [
                                    'field'          => 'StoreId',
                                    'value'          => $this->lsr->getActiveWebStore(),
                                    'condition_type' => 'eq'
                                ],
                                ['field' => 'OfferNo', 'value' => $item->getOfferNo(), 'condition_type' => 'eq'],
                                ['field' => 'Type', 'value' => ReplDiscountType::DISC_OFFER, 'condition_type' => 'eq']
                            ];

                            $criteria = $this->replicationHelper->buildCriteriaForArray($filters, -1, 1);
                            /** @var ReplDiscountSearchResults $replDiscounts */
                            $replDiscounts  = $this->replDiscountRepository->getList($criteria);
                            $skuAmountArray = [];
                            if ($item->getLoyaltySchemeCode() == '' ||
                                $item->getLoyaltySchemeCode() == null
                            ) {
                                $useAllGroupIds   = true;
                                $customerGroupIds = $this->contactHelper->getAllCustomerGroupIds();
                            } else {
                                $useAllGroupIds   = false;
                                $customerGroupIds = [];
                            }
                            if ($replDiscounts->getTotalCount() > 0) {
                                /** We check if offer exist */
                                $this->deleteOfferByName($item->getOfferNo());
                            }
                            /** @var ReplDiscount $replDiscount */
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
                                $discountValue = (string)$replDiscount->getDiscountValue();
                                $appendUom     = '';
                                if (!empty($replDiscount->getUnitOfMeasureId())) {
                                    // @codingStandardsIgnoreLine
                                    $baseUnitOfMeasure = $this->replicationHelper->getBaseUnitOfMeasure($replDiscount->getItemId());
                                    if (($baseUnitOfMeasure != $replDiscount->getUnitOfMeasureId()) ||
                                        ($replDiscount->getVariantId() == '' ||
                                            $replDiscount->getVariantId() == null)) {
                                        $appendUom = '-' . $replDiscount->getUnitOfMeasureId();
                                    }
                                }

                                if ($replDiscount->getVariantId() == '' ||
                                    $replDiscount->getVariantId() == null
                                ) {
                                    $skuAmountArray[$discountValue][] = $replDiscount->getItemId() . $appendUom;
                                } else {
                                    $skuAmountArray[$discountValue][] = $replDiscount->getItemId() . '-' .
                                        $replDiscount->getVariantId() . $appendUom;
                                }
                                $replDiscount->setData('processed_at', $this->replicationHelper->getDateTime());
                                $replDiscount->setData('processed', '1');
                                $replDiscount->setData('is_updated', '0');
                                // @codingStandardsIgnoreStart
                                $this->replDiscountRepository->save($replDiscount);
                                // @codingStandardsIgnoreEnd
                            }

                            if (!empty($skuAmountArray)) {
                                foreach ($skuAmountArray as $value => $key) {
                                    $this->addSalesRule($item, array_unique($key), $customerGroupIds, (float)$value);
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
                                LSR::SC_SUCCESS_CRON_DISCOUNT,
                                $this->store->getId(),
                                false,
                                ScopeInterface::SCOPE_STORES
                            );
                        } else {
                            $this->replicationHelper->updateCronStatus(
                                false,
                                LSR::SC_SUCCESS_CRON_DISCOUNT,
                                $this->store->getId(),
                                false,
                                ScopeInterface::SCOPE_STORES
                            );
                        }
                    } else {
                        // set the status to success if there is nothing to process.
                        $this->replicationHelper->updateCronStatus(
                            true,
                            LSR::SC_SUCCESS_CRON_DISCOUNT,
                            $this->store->getId(),
                            false,
                            ScopeInterface::SCOPE_STORES
                        );
                    }
                    /* Delete the IsDeleted offers */
                    $this->deleteOffers();
                    $this->logger->debug('End DiscountCreateTask for store ' . $this->store->getName());
                }
                $this->lsr->setStoreId(null);
            }
        }
    }

    /**
     * @return array
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws InvalidTransitionException
     * @throws Exception
     */
    public function executeManually($storeData = null)
    {
        $this->execute($storeData);
        $discountsLeftToProcess = $this->getRemainingRecords($storeData->getId());
        return [$discountsLeftToProcess];
    }

    /**
     * Add new catalog rule
     *
     * @param ReplDiscount $replDiscount
     * @param array $skuArray
     * @param $customerGroupIds
     * @param $amount
     * @return void
     */
    public function addSalesRule(ReplDiscount $replDiscount, array $skuArray, $customerGroupIds, $amount = null)
    {
        $websiteIds = [$replDiscount->getScopeId()];

        if ($amount == null) {
            $amount = $replDiscount->getDiscountValue();
        }
        $rule = $this->ruleFactory->create();
        // Create root conditions to match with all child conditions
        $conditions['1']    =
            [
                'type'       => Combine::class,
                'aggregator' => 'all',
                'value'      => 1,
                'new_child'  => ''
            ];
        $conditions['1--1'] =
            [
                'type'      => Product::class,
                'attribute' => 'sku',
                'operator'  => '()',
                'value'     => implode(',', $skuArray)
            ];
        $rule->setName($replDiscount->getOfferNo())
            ->setDescription($replDiscount->getDescription())
            ->setIsActive(1)
            ->setCustomerGroupIds($customerGroupIds)
            ->setWebsiteIds($websiteIds)
            ->setFromDate($replDiscount->getFromDate());

        if (strtolower($replDiscount->getToDate()) != strtolower('1753-01-01T00:00:00')) {
            $rule->setToDate($replDiscount->getToDate());
        }

        /**
         * Default Values for Action Types.
         * by_percent
         * by_fixed
         * to_percent
         * to_fixed
         */
        if ($replDiscount->getDiscountValueType() == 'Amount') {
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
     * @return array|Collection
     * @throws Exception
     */
    public function getUniquePublishedOffers($storeId)
    {
        $publishedOfferIds = [];
        /** @var  Collection $collection */
        $collection = $this->replDiscountCollection->create();
        $collection->addFieldToFilter('scope_id', $storeId);
        $collection->getSelect()
            ->columns('OfferNo')
            ->group('OfferNo');

        $collection->addFieldToFilter(
            ['ToDate', 'ToDate'],
            [['gteq' => $this->replicationHelper->getCurrentDate()], ['eq' => LSR::NO_TIME_LIMIT]]
        );
        $collection->addFieldToFilter(
            'Type',
            ReplDiscountType::DISC_OFFER
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
        $filters  = [];
        $criteria = $this->replicationHelper->buildCriteriaGetDeletedOnly($filters, -1);
        /** @var ReplDiscountSearchResults $replDiscounts */
        $replDiscounts = $this->replDiscountRepository->getList($criteria);
        /** @var ReplDiscount $replDiscount */
        foreach ($replDiscounts->getItems() as $replDiscount) {
            /** @var RuleCollectionFactory $ruleCollection */
            $ruleCollection = $this->ruleCollectionFactory->create();
            $ruleCollection->addFieldToFilter('name', $replDiscount->getOfferNo());
            try {
                foreach ($ruleCollection as $rule) {
                    $this->catalogRule->deleteById($rule->getId());
                }
            } catch (Exception $e) {
                $this->logDetailedException(__METHOD__, $this->store->getName(), $replDiscount->getOfferNo());
                $this->logger->debug($e->getMessage());
                $replDiscount->setData('is_failed', 1);
            }
            $replDiscount->setData('processed_at', $this->replicationHelper->getDateTime());
            $replDiscount->setData('processed', 1);
            $replDiscount->setData('is_updated', 0);
            // @codingStandardsIgnoreLine
            $this->replDiscountRepository->save($replDiscount);
        }
    }

    /**
     * @param $name
     */
    public function deleteOfferByName($name)
    {
        $websiteIds     = [$this->store->getWebsiteId()];
        $ruleCollection = $this->ruleCollectionFactory->create();
        $ruleCollection->addFieldToFilter('name', $name);
        $ruleCollection->addFieldToFilter('website_ids', $websiteIds);
        try {
            foreach ($ruleCollection as $rule) {
                $this->catalogRule->deleteById($rule->getId());
            }
        } catch (Exception $e) {
            $this->logDetailedException(__METHOD__, $this->store->getName(), $name);
            $this->logger->debug($e->getMessage());
        }
    }

    /**
     * @param $storeId
     * @return int
     * @throws Exception
     */
    public function getRemainingRecords($storeId)
    {
        if (!$this->remainingRecords) {
            $store_id               = $this->lsr->getActiveWebStore();
            $filtersStatus          = [
                ['field' => 'scope_id', 'value' => $storeId, 'condition_type' => 'eq'],
                ['field' => 'StoreId', 'value' => $store_id, 'condition_type' => 'eq'],
                ['field' => 'Type', 'value' => ReplDiscountType::DISC_OFFER, 'condition_type' => 'eq'],
                ['field' => 'ToDate', 'value' => $this->replicationHelper->getCurrentDate(), 'condition_type' => 'gteq']
            ];
            $parameter              = ['field' => 'ToDate', 'value' => LSR::NO_TIME_LIMIT, 'condition_type' => 'eq'];
            $criteriaTotal          = $this->replicationHelper->buildCriteriaForArray($filtersStatus, 2, 1, $parameter);
            $this->remainingRecords = $this->replDiscountRepository->getList($criteriaTotal)
                ->getTotalCount();
        }
        return $this->remainingRecords;
    }

    /**
     * Log Detailed exception
     *
     * @param $method
     * @param $storeName
     * @param $itemId
     * @return void
     */
    public function logDetailedException($method, $storeName, $itemId)
    {
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
     * Get current scope id
     *
     * @return int
     */
    public function getScopeId()
    {
        return $this->store->getWebsiteId();
    }
}
