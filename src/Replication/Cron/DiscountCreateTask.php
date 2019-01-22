<?php
// @codingStandardsIgnoreFile

namespace Ls\Replication\Cron;

use Ls\Core\Model\LSR;
use Ls\Replication\Helper\ReplicationHelper;
use Ls\Omni\Helper\ContactHelper;
use Magento\CatalogRule\Api\CatalogRuleRepositoryInterface;
use Magento\CatalogRule\Model\RuleFactory;
use Magento\CatalogRule\Model\Rule\Job;
use Ls\Replication\Model\ResourceModel\ReplDiscount\CollectionFactory;
use Ls\Replication\Api\ReplDiscountRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Class DiscountCreateTask
 * @package Ls\Replication\Cron
 * This cron will create catalog rules in order to integrate the preactive discounts which are independent of any Member Limitation
 * One Sales Rule  = All discounts based on Published Offer.
 * Condition will be to have any of the value equal to the SKUS found in it.
 * Priority will be same for published offer as it was created in Nav.
 *
 */
class DiscountCreateTask
{
    /**
     * @var CatalogRuleRepositoryInterface
     */
    protected $catalogRule;

    /**
     * @var RuleFactory
     */
    protected $ruleFactory;

    /**
     * @var Job
     */
    protected $jobApply;

    /**
     * @var ReplDiscountRepositoryInterface
     */
    protected $replDiscountRepository;

    /**
     * @var LSR
     */
    protected $_lsr;

    /**
     * @var LoggerInterface
     */

    protected $logger;

    /**
     * @var ReplicationHelper
     */
    protected $_replicationHelper;


    /**
     * @var ContactHelper
     */
    protected $_contactHelper;


    /**
     * @var CollectionFactory
     */
    protected $replDiscountCollection;


    /**
     * DiscountCreateTask constructor.
     * @param RuleFactory $ruleFactory
     * @param RuleRepository $ruleRepository
     * @param Rule $rule
     * @param ReplDiscountRepository $replDiscountRepository
     * @param ReplicationHelper $replicationHelper
     * @param LSR $LSR
     * @param LoggerInterface $logger
     */
    public function __construct(
        CatalogRuleRepositoryInterface $catalogRule,
        RuleFactory $ruleFactory,
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
        $this->jobApply = $jobApply;
        $this->replDiscountRepository = $replDiscountRepository;
        $this->_replicationHelper = $replicationHelper;
        $this->_contactHelper = $contactHelper;
        $this->_lsr = $LSR;
        $this->replDiscountCollection = $replDiscountCollection;
        $this->logger = $logger;
    }


    /**
     *
     */
    public function execute()
    {
        /**
         * Get all Unique Publish offer so that we can create catalog rules based on that.
         * Only gonna work if everything is good to go.
         * And the web store is being set in the Magento.
         * And we need to apply only those rules which are associated to the store assigned to it.
         */
        $CronProductCheck = $this->_lsr->getStoreConfig(LSR::SC_SUCCESS_CRON_PRODUCT);
        if ($CronProductCheck == 1) {
            if ($this->_lsr->isLSR()) {
                $store_id = $this->_lsr->getDefaultWebStore();
                $publishedOfferCollection = $this->getUniquePublishedOffers();
                if (!empty($publishedOfferCollection)) {
                    /** @var \Ls\Replication\Model\ReplDiscount $item */
                    foreach ($publishedOfferCollection as $item) {
                        $filters = [
                            //array('field' => 'StoreId', 'value' => $store_id, 'condition_type' => 'eq'),
                            ['field' => 'OfferNo', 'value' => $item->getOfferNo(), 'condition_type' => 'eq']
                        ];

                        $criteria = $this->_replicationHelper->buildCriteriaForArray($filters, 100);

                        /** @var \Ls\Replication\Model\ReplDiscountSearchResults $replDiscounts */
                        $replDiscounts = $this->replDiscountRepository->getList($criteria);

                        $skuArray = [];

                        if ($item->getLoyaltySchemeCode() == '' || is_null($item->getLoyaltySchemeCode())) {
                            $useAllGroupIds = true;
                            $customerGroupIds = $this->_contactHelper->getAllCustomerGroupIds();
                        } else {
                            $useAllGroupIds = false;
                            $customerGroupIds = [];
                        }

                        /** @var \Ls\Replication\Model\ReplDiscount $replDiscount */
                        foreach ($replDiscounts->getItems() as $replDiscount) {
                            // To check if discounts groups are specific for any Member Scheme.
                            if (!$useAllGroupIds and !in_array($this->_contactHelper->getCustomerGroupIdByName($replDiscount->getLoyaltySchemeCode()), $customerGroupIds)) {
                                $customerGroupIds[] = $this->_contactHelper->getCustomerGroupIdByName($replDiscount->getLoyaltySchemeCode());
                            }

                            if ($replDiscount->getVariantId() == '' || is_null($replDiscount->getVariantId())) {
                                $skuArray[] = $replDiscount->getItemId();
                            } else {
                                $skuArray[] = $replDiscount->getItemId() . '-' . $replDiscount->getVariantId();
                            }
                            $replDiscount->setData('processed', '1');
                            $this->replDiscountRepository->save($replDiscount);
                        }
                        if (!empty($skuArray)) {
                            $this->addSalesRule($item, $skuArray, $customerGroupIds);
                        }
                        $this->jobApply->applyAll();
                    }
                    $criteriaTotal = $this->_replicationHelper->buildCriteriaForArray([], 100);
                    /** @var \Ls\Replication\Model\ReplDiscountSearchResults $replDiscounts */
                    $replDiscountsTotal = $this->replDiscountRepository->getList($criteriaTotal);
                    if (count($replDiscountsTotal->getItems()) == 0) {
                        $this->_replicationHelper->updateCronStatus(true, LSR::SC_SUCCESS_CRON_DISCOUNT);
                    } else {
                        $this->_replicationHelper->updateCronStatus(false, LSR::SC_SUCCESS_CRON_DISCOUNT);
                    }
                }
            }
        } else {
            $this->logger->debug("Discount Replication cron fails because product replication cron not executed successfully.");
        }
    }

    /**
     * @return array
     */
    public function executeManually()
    {
        $discountsLeftToProcess = 0;
        $this->execute();
        return [$discountsLeftToProcess];
    }

    /**
     * @param \Ls\Replication\Model\ReplDiscount $replDiscount
     * @param array $skuArray
     * @param $customerGroupIds
     */
    protected function addSalesRule(\Ls\Replication\Model\ReplDiscount $replDiscount, array $skuArray, $customerGroupIds)
    {


        if ($replDiscount instanceof \Ls\Replication\Model\ReplDiscount) {
            $websiteIds = $this->_replicationHelper->getAllWebsitesIds();
            $rule = $this->ruleFactory->create();

            // create root conditions to match with all child conditions
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
                ->setDescription($replDiscount->getOfferNo())
                ->setIsActive(1)
                ->setCustomerGroupIds($customerGroupIds)
                ->setWebsiteIds($websiteIds)
                ->setFromDate($replDiscount->getFromDate());

            // Discounts for aspecific time.
            if (strtolower($replDiscount->getToDate()) != strtolower('1753-01-01T00:00:00')) {
                $rule->setToDate($replDiscount->getToDate());
            }
            $rule->setSimpleAction('by_percent')//THis is fixed from Omni so we dont have to change
            ->setDiscountAmount($replDiscount->getDiscountValue())
                ->setStopRulesProcessing(1)// NAV only allow one preactive discount at the time, so yes we dont need this dynamic from Nav.
                ->setSortOrder($replDiscount->getPriorityNo());

            /**
             * Default Values for Action Types.
             * by_percent
             * by_fixed
             * to_percent
             * to_fixed.
             *
             */
            $rule->setData('conditions', $conditions);
            $validateResult = $rule->validateData(new \Magento\Framework\DataObject($rule->getData()));


            if ($validateResult !== true) {
                foreach ($validateResult as $errorMessage) {
                    $this->logger->debug($errorMessage);
                }
                return;
            }
            try {
                $rule->loadPost($rule->getData());
                $rule->save();
            } catch (\Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }
    }

    /**
     * @return array|\Ls\Replication\Model\ResourceModel\ReplDiscount\Collection
     */
    protected function getUniquePublishedOffers()
    {

        $publishedOfferIds = [];
        /** @var  \Ls\Replication\Model\ResourceModel\ReplDiscount\Collection $collection */
        $collection = $this->replDiscountCollection->create();
        $collection->getSelect()
            ->columns('OfferNo')
            ->group('OfferNo');

        if ($collection->getSize() > 0) {
            return $collection;
        }
        return $publishedOfferIds;
    }
}
