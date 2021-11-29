<?php

namespace Ls\Replication\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Logger\Logger;
use \Ls\Replication\Model\ReplCountryCodeRepository;
use \Ls\Replication\Model\ReplTaxSetupRepository;
use Ls\Replication\Model\SearchResultInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Tax\Api\Data\TaxRateInterface;
use Magento\Tax\Api\Data\TaxRateInterfaceFactory;
use Magento\Tax\Api\Data\TaxRuleInterface;
use Magento\Tax\Api\Data\TaxRuleInterfaceFactory;
use Magento\Tax\Api\TaxRateRepositoryInterface;
use Magento\Tax\Api\TaxRuleRepositoryInterface;
use Magento\Tax\Model\Calculation\Rate;
use Magento\Tax\Model\Calculation\Rule;

/**
 * Create tax_rules and tax_rates cron
 */
class TaxRulesCreateTask
{
    /** @var Logger */
    public $logger;

    /** @var ReplicationHelper */
    public $replicationHelper;

    /** @var LSR */
    public $lsr;

    /** @var bool */
    public $cronStatus = false;

    /** @var int */
    public $remainingRecords;

    /** @var StoreInterface $store */
    public $store;

    /**
     * @var ReplCountryCodeRepository
     */
    public $replCountryCodeRepository;

    /**
     * @var ReplTaxSetupRepository
     */
    public $replTaxSetupRepository;

    /**
     * @var TaxRateInterfaceFactory
     */
    public $taxRateInterfaceFactory;

    /**
     * @var TaxRateRepositoryInterface
     */
    public $taxRateRepository;

    /**
     * @var TaxRuleInterfaceFactory
     */
    public $taxRuleInterfaceFactory;

    /**
     * @var TaxRuleRepositoryInterface
     */
    public $taxRuleRepository;

    public function __construct(
        Logger $logger,
        ReplicationHelper $replicationHelper,
        LSR $LSR,
        ReplCountryCodeRepository $replCountryCodeRepository,
        ReplTaxSetupRepository $replTaxSetupRepository,
        TaxRateInterfaceFactory $taxRateInterfaceFactory,
        TaxRateRepositoryInterface $taxRateRepository,
        TaxRuleInterfaceFactory $taxRuleInterfaceFactory,
        TaxRuleRepositoryInterface $taxRuleRepository
    ) {
        $this->logger                    = $logger;
        $this->replicationHelper         = $replicationHelper;
        $this->lsr                       = $LSR;
        $this->replCountryCodeRepository = $replCountryCodeRepository;
        $this->replTaxSetupRepository    = $replTaxSetupRepository;
        $this->taxRateInterfaceFactory   = $taxRateInterfaceFactory;
        $this->taxRateRepository         = $taxRateRepository;
        $this->taxRuleInterfaceFactory   = $taxRuleInterfaceFactory;
        $this->taxRuleRepository         = $taxRuleRepository;
    }

    /**
     * @param null $storeData
     * @return int[]
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function executeManually($storeData = null)
    {
        $this->execute($storeData);
        $taxRulesLeftToProcess = $this->getRemainingRecords($storeData);
        return [$taxRulesLeftToProcess];
    }

    /**
     * @param null $storeData
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function execute($storeData = null)
    {
        /**
         * Get all the available stores config in the Magento system
         */
        if (!empty($storeData) && $storeData instanceof StoreInterface) {
            $stores = [$storeData];
        } else {
            $stores = $this->lsr->getAllStores();
        }
        if (!empty($stores)) {
            foreach ($stores as $store) {
                $this->lsr->setStoreId($store->getId());
                $this->store = $store;

                if ($this->lsr->isLSR($this->store->getId())) {
                    $this->logger->debug('Running TaxRulesCreateTask for Store ' . $this->store->getName());
                    $this->replicationHelper->updateConfigValue(
                        $this->replicationHelper->getDateTime(),
                        LSR::SC_CRON_TAX_RULES_CONFIG_PATH_LAST_EXECUTE,
                        $this->store->getId()
                    );
                    $countryCodes = $this->getCountryCodes();

                    foreach ($countryCodes->getItems() as $countryCode) {
                        $taxPostGroup = $countryCode->getTaxPostGroup();
                        $rates        = $this->getRatesGivenBusinessTaxGroup(
                            $taxPostGroup,
                            $this->store->getId()
                        )->getItems();

                        foreach ($rates as $rate) {
                            $taxClass = $this->replicationHelper->getTaxClassGivenName($rate->getProductTaxGroup());
                            $taxRate  = $this->getTaxCalculationRateGivenInfo($countryCode, $rate);
                            $this->getTaxCalculationRuleGivenInfo($taxRate, $taxClass);
                        }

                        $countryCode->setData('processed_at', $this->replicationHelper->getDateTime())
                            ->setData('processed', 1)
                            ->setData('is_updated', 0);
                        // @codingStandardsIgnoreLine
                        $this->replCountryCodeRepository->save($countryCode);
                    }

                    if ($this->getRemainingRecords() == 0) {
                        $this->cronStatus = true;
                    }

                    $this->replicationHelper->updateCronStatus(
                        $this->cronStatus,
                        LSR::SC_SUCCESS_CRON_TAX_RULES,
                        $store->getId()
                    );
                    $this->logger->debug('TaxRulesCreateTask Completed for Store ' . $this->store->getName());
                }
                $this->lsr->setStoreId(null);
            }
        }
    }

    /**
     * @return SearchResultInterface
     */
    public function getCountryCodes()
    {
        $filters  = [
            ['field' => 'TaxPostGroup', 'value' => null, 'condition_type' => 'neq'],
            ['field' => 'CustomerNo', 'value' => null, 'condition_type' => 'neq']
        ];
        $criteria = $this->replicationHelper->buildCriteriaForArray($filters, -1);

        return $this->replCountryCodeRepository->getList($criteria);
    }

    /**
     * @param $group
     * @param $scopeId
     * @return SearchResultInterface
     */
    public function getRatesGivenBusinessTaxGroup($group, $scopeId)
    {
        $filters  = [
            ['field' => 'BusinessTaxGroup', 'value' => $group, 'condition_type' => 'eq'],
            ['field' => 'scope_id', 'value' => $scopeId, 'condition_type' => 'eq']
        ];
        $criteria = $this->replicationHelper->buildCriteriaForDirect($filters, -1);

        return $this->replTaxSetupRepository->getList($criteria);
    }

    /**
     * @param $countryCode
     * @param $rate
     * @return TaxRateInterface
     * @throws InputException
     */
    public function getTaxCalculationRateGivenInfo($countryCode, $rate)
    {
        /**
         * @var Rate $taxRate
         */
        $taxRate = $this->taxRateInterfaceFactory->create();
        $taxRate->setRate($rate->getTaxPercent())
            ->setTaxCountryId($countryCode->getCode())
            ->setTaxRegionId(0)
            ->setTaxPostcode('*')
            ->setCode($countryCode->getCode() . '-*-*-' . $rate->getProductTaxGroup());
        return $this->taxRateRepository->save($taxRate);
    }

    /**
     * @param $taxRate
     * @param $taxClass
     * @return TaxRuleInterface
     * @throws InputException
     */
    public function getTaxCalculationRuleGivenInfo($taxRate, $taxClass)
    {
        /**
         * @var Rule $taxRule
         */
        $taxRule = $this->taxRuleInterfaceFactory->create();
        $taxRule->setCode($taxRate->getCode())
            ->setPosition(0)
            ->setPriority(0)
            ->setCustomerTaxClassIds([3]) //3 for selecting Retail Customer Class
            ->setProductTaxClassIds([2, $taxClass->getClassId()]) //2 for selecting Taxable Product Class else no tax
            ->setTaxRateIds([$taxRate->getTaxCalculationRateId()]);
        return $this->taxRuleRepository->save($taxRule);
    }

    /**
     * @return int
     */
    public function getRemainingRecords()
    {
        if (!$this->remainingRecords) {
            $this->remainingRecords = $this->getCountryCodes()->getTotalCount();
        }

        return $this->remainingRecords;
    }
}
