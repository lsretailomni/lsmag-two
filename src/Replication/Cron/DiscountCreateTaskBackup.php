<?php

namespace Ls\Replication\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Helper\ReplicationHelper;

use Magento\CatalogRule\Model\Rule;
use Magento\SalesRule\Model\RuleFactory;
use Magento\SalesRule\Model\RuleRepository;
use \Ls\Replication\Api\ReplDiscountRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Class DiscountCreateTask
 * @package Ls\Replication\Cron
 * This cron will create catalog rules in order to integrate the preactive discounts which are independent of any Member Limitation
 * One Sales Rule  = All discounts based on Published Offer.
 * Condition will be to have any of the value equal to the SKUS found in it.
 *
 */
class DiscountCreateTask
{
    /**
     * @var RuleFactory
     */
    protected $ruleFactory;
    /**
     * @var RuleRepository
     */
    protected $ruleRepository;
    /**
     * @var Rule
     */
    protected $rule;
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
        RuleFactory $ruleFactory,
        RuleRepository $ruleRepository,
        Rule $rule,
        ReplDiscountRepository $replDiscountRepository,
        ReplicationHelper $replicationHelper,
        LSR $LSR,
        LoggerInterface $logger
    ) {
        $this->ruleFactory = $ruleFactory;
        $this->ruleRepository = $ruleRepository;
        $this->rule = $rule;
        $this->replDiscountRepository = $replDiscountRepository;
        $this->_replicationHelper       =   $replicationHelper;
        $this->_lsr = $LSR;
        $this->logger = $logger;
    }

    /**
     * Create discount for the items
     */
    public function execute()
    {
        $this->logger->debug("Running DiscountCreateTask");
        if ($this->_lsr->isLSR()) {
            $criteria = $this->buildCriteriaForNewItems();
            /** @var \Ls\Replication\Model\ReplDiscountSearchResults $replDiscounts */
            $replDiscounts = $this->replDiscountRepository->getList($criteria);
            foreach ($replDiscounts->getItems() as $replDiscount) {
                $this->addSalesRule($replDiscount);
            }            
        }
        $this->logger->debug("Finish DiscountCreateTask");
    }


    /**
     * @param $replDiscount
     */
    protected function addSalesRule($replDiscount)
    {
        /** @var \Magento\CatalogRule\Model\Rule $rule */
        $rule = $this->ruleFactory->create();
        //Generated dynamic name to display
        $name = $replDiscount->getDiscountValue() . '% discount on ' . $replDiscount->getItemId() . ' with minimum purchase of ' . $replDiscount->getMinimumQuantity() . ' qty';
        $conditions["1"] = array
        (
            "type" => "Magento\SalesRule\Model\Rule\Condition\Combine",
            "aggregator" => "all",
            "value" => 1,
            "new_child" => ""
        );
        $conditions["1--1"] = array
        (
            "type" => "Magento\SalesRule\Model\Rule\Condition\Product\Found",
            "aggregator" => "all",
            "value" => 1,
            "new_child" => ""
        );
        $conditions["1--1--1"] = array
        (
            "type" => "Magento\SalesRule\Model\Rule\Condition\Product",
            "attribute" => "sku",
            "operator" => "==",
            "value" => $replDiscount->getItemId()
        );
        $actions = array(
            "1" => array(
                "type" => "Magento\SalesRule\Model\Rule\Condition\Product",
                "aggregator" => "all",
                "value" => "1",
                "new_child" => false
            ),
            "1--1" => array(
                "type" => "Magento\SalesRule\Model\Rule\Condition\Product",
                "attribute" => "sku",
                "operator" => "==",
                "value" => $replDiscount->getItemId()
            )
        );
        $data = [
            'name' => $name,
            'description' => $name,
            'is_active' => 1,
            'coupon_type' => 1,
            'customer_group_ids' => array(0, 1, 2, 3), // TODO update to Dynamic
            'website_ids' => array(1), // TODO update to Dynamic
            'from_date' => $replDiscount->getFromDate(),
            'to_date' => $replDiscount->getToDate(),
            'simple_action' => 'by_percent',// TODO change to enum after Omni new release
            'discount_amount' => $replDiscount->getDiscountValue(),
            'discount_step' => $replDiscount->getMinimumQuantity(),
            'stop_rules_processing' => 1,
            'conditions' => $conditions,
            'actions' => $actions
        ];
        $rule->setData($data);
        $validateResult = $rule->validateData(new \Magento\Framework\DataObject($rule->getData()));
        if ($validateResult !== true) {
            foreach ($validateResult as $errorMessage) {
                $this->logger->debug($errorMessage);
            }
            return;
        }
        try {
            $rule->loadPost($rule->getData());
            //TODO Need to change because cannot find the loadPost function in Repository for conditions to work
            $rule->save();
            /* Set the process flag to true. */
            $replDiscount->setData('processed', '1');
            $replDiscount->save();
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
        }
    }

    /**
     * @param string $filtername
     * @param string $filtervalue
     * @return \Magento\Framework\Api\SearchCriteria
     */
    protected function buildCriteriaForNewItems($filtername = '', $filtervalue = '', $conditionType = 'eq')
    {
        // creating search criteria for two fields
        // processed = 0 which means not yet processed
        $attr_processed = $this->filterBuilder->setField('processed')
            ->setValue('0')
            ->setConditionType('eq')
            ->create();
        // is_updated = 1 which means may be processed already but is updated on omni end
        $attr_is_updated = $this->filterBuilder->setField('is_updated')
            ->setValue('1')
            ->setConditionType('eq')
            ->create();
        // building OR condition between the above two criteria
        $filterOr = $this->filterGroupBuilder
            ->addFilter($attr_processed)
            ->addFilter($attr_is_updated)
            ->create();
        // adding critera into where clause.
        $criteria = $this->searchCriteriaBuilder->setFilterGroups([$filterOr]);
        if ($filtername != '' && $filtervalue != '') {
            $criteria->addFilter(
                $filtername, $filtervalue, $conditionType
            );
        }
        return $criteria->create();
    }


}
