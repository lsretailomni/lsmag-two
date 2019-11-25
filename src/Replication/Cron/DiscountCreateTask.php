<?php

namespace Ls\Replication\Cron;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\ReplDiscountType;
use \Ls\Omni\Helper\ContactHelper;
use \Ls\Replication\Api\ReplDiscountRepositoryInterface;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Model\ReplDiscount;
use \Ls\Replication\Model\ReplDiscountSearchResults;
use \Ls\Replication\Model\ResourceModel\ReplDiscount\Collection;
use \Ls\Replication\Model\ResourceModel\ReplDiscount\CollectionFactory;
use \Ls\Replication\Logger\Logger;
use Magento\CatalogRule\Api\CatalogRuleRepositoryInterface;
use Magento\CatalogRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use Magento\CatalogRule\Model\Rule\Job;
use Magento\CatalogRule\Model\RuleFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InvalidTransitionException;

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
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws InvalidTransitionException
     */
    public function execute()
    {
        if ($this->lsr->isLSR()) {
            $this->replicationHelper->updateConfigValue(date('d M,Y h:i:s A'), self::CONFIG_PATH_LAST_EXECUTE);
            $store_id                 = $this->lsr->getDefaultWebStore();
            $publishedOfferCollection = $this->getUniquePublishedOffers();
            if (!empty($publishedOfferCollection)) {
                $reindexRules = false;
                /** @var ReplDiscount $item */
                foreach ($publishedOfferCollection as $item) {
                    $filters = [
                        ['field' => 'StoreId', 'value' => $store_id, 'condition_type' => 'eq'],
                        ['field' => 'OfferNo', 'value' => $item->getOfferNo(), 'condition_type' => 'eq'],
                        ['field' => 'Type', 'value' => ReplDiscountType::DISC_OFFER, 'condition_type' => 'eq']
                    ];

                    $criteria = $this->replicationHelper->buildCriteriaForArray($filters, -1, 1);
                    /** @var ReplDiscountSearchResults $replDiscounts */
                    $replDiscounts  = $this->replDiscountRepository->getList($criteria);
                    $skuAmountArray = $diffValueArray = $sameValueArray = [];
                    if ($item->getLoyaltySchemeCode() == '' ||
                        $item->getLoyaltySchemeCode() == null
                    ) {
                        $useAllGroupIds   = true;
                        $customerGroupIds = $this->contactHelper->getAllCustomerGroupIds();
                    } else {
                        $useAllGroupIds   = false;
                        $customerGroupIds = [];
                    }
                    if ($replDiscounts->getItems()) {
                        /** We check if offer exist */
                        $deleteStatus = $this->deleteOfferByName($item->getOfferNo());
                        if ($deleteStatus) {
                            $criteriaAfterDelete = $this->replicationHelper->buildCriteriaForArray(
                                $filters,
                                -1,
                                1
                            );
                            /** @var ReplDiscountSearchResults $replDiscounts */
                            $replDiscounts = $this->replDiscountRepository->getList($criteriaAfterDelete);
                        }
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
                        if ($replDiscount->getVariantId() == '' ||
                            $replDiscount->getVariantId() == null
                        ) {
                            $skuAmountArray[$replDiscount->getItemId()] = $replDiscount->getDiscountValue();
                        } else {
                            $skuAmountArray[$replDiscount->getItemId() . '-' .
                            $replDiscount->getVariantId()] = $replDiscount->getDiscountValue();
                        }

                        $replDiscount->setData('processed', '1');
                        $replDiscount->setData('is_updated', '0');
                        // @codingStandardsIgnoreStart
                        $this->replDiscountRepository->save($replDiscount);
                        // @codingStandardsIgnoreEnd
                    }

                    $counts         = array_count_values($skuAmountArray);
                    $sameValueArray = array_filter($skuAmountArray, function ($value) use ($counts) {
                        return $counts[$value] > 1;
                    });

                    $diffValueArray = array_filter($skuAmountArray, function ($value) use ($counts) {
                        return $counts[$value] == 1;
                    });

                    if (!empty($diffValueArray)) {
                        foreach ($diffValueArray as $key => $value) {
                            $this->addSalesRule($item, array($key), $customerGroupIds, $value);
                        }
                    }
                    if (!empty($sameValueArray)) {
                        $this->addSalesRule($item, array_keys($sameValueArray), $customerGroupIds);
                    }
                    $reindexRules = true;
                }
                if ($reindexRules) {
                    $this->jobApply->applyAll();
                }
                $filtersStatus = [
                    ['field' => 'Type', 'value' => ReplDiscountType::DISC_OFFER, 'condition_type' => 'eq']
                ];
                $criteriaTotal = $this->replicationHelper->buildCriteriaForArray($filtersStatus, -1, 1);
                /** @var ReplDiscountSearchResults $replDiscounts */
                $replDiscountsTotal = $this->replDiscountRepository->getList($criteriaTotal);
                if (count($replDiscountsTotal->getItems()) == 0) {
                    $this->replicationHelper->updateCronStatus(true, LSR::SC_SUCCESS_CRON_DISCOUNT);
                } else {
                    $this->replicationHelper->updateCronStatus(false, LSR::SC_SUCCESS_CRON_DISCOUNT);
                }
            }
            /* Delete the IsDeleted offers */
            $this->deleteOffers();
        }
    }

    /**
     * @return array
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws InvalidTransitionException
     */
    public function executeManually()
    {
        $discountsLeftToProcess = 0;
        $this->execute();
        return [$discountsLeftToProcess];
    }

    /**
     * @param ReplDiscount $replDiscount
     * @param array $skuArray
     * @param $customerGroupIds
     * @param null $amount
     */
    public function addSalesRule(ReplDiscount $replDiscount, array $skuArray, $customerGroupIds, $amount = null)
    {

        if ($replDiscount instanceof ReplDiscount) {
            $websiteIds = $this->replicationHelper->getAllWebsitesIds();
            if ($amount == null) {
                $amount = $replDiscount->getDiscountValue();
            }
            $rule = $this->ruleFactory->create();
            // Create root conditions to match with all child conditions
            $conditions['1']    =
                [
                    'type'       => 'Magento\CatalogRule\Model\Rule\Condition\Combine',
                    'aggregator' => 'all',
                    'value'      => 1,
                    'new_child'  => ''
                ];
            $conditions['1--1'] =
                [
                    'type'      => 'Magento\CatalogRule\Model\Rule\Condition\Product',
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
            // Discounts for specific time
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
                $this->logger->debug($e->getMessage());
                $replDiscount->setData('is_failed', 1);
                // @codingStandardsIgnoreLine
                $this->replDiscountRepository->save($replDiscount);
            }
        }
    }

    /**
     * @return array|Collection
     */
    public function getUniquePublishedOffers()
    {
        $publishedOfferIds = [];
        /** @var  Collection $collection */
        $collection = $this->replDiscountCollection->create();
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
        $filters  = [
            ['field' => 'Type', 'value' => ReplDiscountType::DISC_OFFER, 'condition_type' => 'eq']
        ];
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
                $this->logger->debug($e->getMessage());
                $replDiscount->setData('is_failed', 1);
            }
            $replDiscount->setData('processed', 1);
            // @codingStandardsIgnoreLine
            $this->replDiscountRepository->save($replDiscount);
        }
    }

    /**
     * Delete offer by name
     * @param $name
     * @return bool
     */
    public function deleteOfferByName($name)
    {
        $ruleCollection = $this->ruleCollectionFactory->create();
        $ruleCollection->addFieldToFilter('name', $name);
        try {
            foreach ($ruleCollection as $rule) {
                $this->catalogRule->deleteById($rule->getId());
                $filters  = [
                    ['field' => 'OfferNo', 'value' => $name, 'condition_type' => 'eq'],
                    ['field' => 'Type', 'value' => ReplDiscountType::DISC_OFFER, 'condition_type' => 'eq']
                ];
                $criteria = $this->replicationHelper->buildCriteriaForDirect($filters, -1);
                /** @var ReplDiscountSearchResults $replDiscounts */
                $replDiscounts = $this->replDiscountRepository->getList($criteria);
                /** @var ReplDiscount $replDiscount */
                foreach ($replDiscounts->getItems() as $replDiscount) {
                    $replDiscount->setData('processed', 0);
                    $replDiscount->setData('is_updated', 0);
                    // @codingStandardsIgnoreLine
                    $this->replDiscountRepository->save($replDiscount);
                }
                return true;
            }
        } catch (Exception $e) {
            $this->logger->debug($e->getMessage());
        }
        return false;
    }
}
