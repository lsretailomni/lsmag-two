<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Api\ReplCountryCodeRepositoryInterface;
use \Ls\Replication\Cron\ReplEcommCountryCodeTask;
use \Ls\Replication\Cron\ReplEcommStoresTask;
use \Ls\Replication\Cron\ReplEcommTaxSetupTask;
use \Ls\Replication\Cron\TaxRulesCreateTask;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Test\Fixture\FlatDataReplication;
use \Ls\Replication\Test\Integration\AbstractIntegrationTest;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Api\TaxClassRepositoryInterface;
use Magento\Tax\Api\TaxRateRepositoryInterface;
use Magento\Tax\Api\TaxRuleRepositoryInterface;
use Magento\Tax\Model\ClassModel;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea crontab
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
#[
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommTaxSetupTask::class,
            'scope' => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommCountryCodeTask::class,
            'scope' => ScopeInterface::SCOPE_WEBSITE
        ]
    ),
    DataFixture(
        FlatDataReplication::class,
        [
            'job_url' => ReplEcommStoresTask::class,
            'scope' => ScopeInterface::SCOPE_WEBSITE
        ]
    )
]
class TaxCreateTaskTest extends TestCase
{
    public $objectManager;
    public $cron;
    public $lsr;
    public $storeManager;
    public $replicationHelper;
    public $replCountryCodeRepository;
    public $taxRateRepository;
    public $taxClassRepository;
    public $taxRuleRepository;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager             = Bootstrap::getObjectManager();
        $this->cron                      = $this->objectManager->create(TaxRulesCreateTask::class);
        $this->lsr                       = $this->objectManager->create(\Ls\Core\Model\Lsr::class);
        $this->storeManager              = $this->objectManager->get(StoreManagerInterface::class);
        $this->replicationHelper         = $this->objectManager->get(ReplicationHelper::class);
        $this->replCountryCodeRepository = $this->objectManager->get(ReplCountryCodeRepositoryInterface::class);
        $this->taxRateRepository         = $this->objectManager->get(TaxRateRepositoryInterface::class);
        $this->taxClassRepository        = $this->objectManager->get(TaxClassRepositoryInterface::class);
        $this->taxRuleRepository         = $this->objectManager->get(TaxRuleRepositoryInterface::class);
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'website'),
        Config(LSR::SC_REPLICATION_DEFAULT_BATCHSIZE, AbstractIntegrationTest::DEFAULT_BATCH_SIZE)
    ]
    public function testExecute()
    {
        $this->executeUntilReady();
        $storeId = $this->storeManager->getStore()->getId();
        $scopeId = $this->storeManager->getWebsite()->getId();
        $this->assertCronSuccess(
            [
                LSR::SC_SUCCESS_CRON_TAX_RULES,
            ],
            $storeId
        );
        $replCountryCode = $this->getCountryCode(
            AbstractIntegrationTest::SAMPLE_COUNTRY_CODE,
            $scopeId
        );
        $defaultTaxPostGroup = current($this->cron->getDefaultTaxPostGroup($scopeId));

        $taxPostGroup    = $replCountryCode->getTaxPostGroup();
        if (!$taxPostGroup) {
            $taxPostGroup = $defaultTaxPostGroup->getTaxGroup();
        }
        $rates = $this->cron->getRatesGivenBusinessTaxGroup(
            $taxPostGroup,
            $scopeId
        )->getItems();

        foreach ($rates as $rate) {
            $taxClassList = $this->getTaxClassList($rate);
            $this->assertEquals(1, $taxClassList->getTotalCount());
            $taxClass = current($taxClassList->getItems());
            $replTaxRate = $this->getTaxRate($replCountryCode, $rate, $scopeId);
            $this->assertNotNull($replTaxRate->getData('tax_calculation_rate_id'));
            $this->assertEquals($replTaxRate->getRate(), $rate->getTaxPercent());
            $taxRule = $this->getTaxRule($replTaxRate);
            $this->assertNotNull($taxRule->getData('tax_calculation_rule_id'));
            $this->assertEqualsCanonicalizing(
                [2, $taxClass->getClassId()],
                $taxRule->getData('product_tax_classes')
            );
            $this->assertEqualsCanonicalizing([3], $taxRule->getData('customer_tax_classes'));
            $this->assertEqualsCanonicalizing(
                [$replTaxRate->getData('tax_calculation_rate_id')],
                $taxRule->getData('tax_rates')
            );
        }
    }

    public function getTaxRule($replTaxRate)
    {
        $filters     = [
            [
                'field' => 'code',
                'value' => $replTaxRate->getCode(),
                'condition_type' => 'eq'
            ],
        ];
        $criteria    = $this->replicationHelper->buildCriteriaForDirect($filters, -1, 0);
        $replTaxRule = current($this->taxRuleRepository->getList($criteria)->getItems());

        return $replTaxRule;
    }

    public function getCountryCode($countryCode, $scopeId)
    {
        $filters         = [
            ['field' => 'scope_id', 'value' => $scopeId, 'condition_type' => 'eq'],
            ['field' => 'Code', 'value' => $countryCode, 'condition_type' => 'eq']
        ];
        $criteria        = $this->replicationHelper->buildCriteriaForDirect($filters, -1);
        $replCountryCode = current($this->replCountryCodeRepository->getList($criteria)->getItems());

        return $replCountryCode;
    }

    public function getTaxRate($replCountryCode, $rate, $scopeId)
    {
        $filters     = [
            [
                'field' => 'code',
                'value' => $replCountryCode->getCode() . '-*-*-' . $rate->getProductTaxGroup() . '-' . $scopeId,
                'condition_type' => 'eq'
            ],
        ];
        $criteria    = $this->replicationHelper->buildCriteriaForDirect($filters, -1, 0);
        $replTaxRate = current($this->taxRateRepository->getList($criteria)->getItems());

        return $replTaxRate;
    }

    public function getTaxClassList($rate)
    {
        $criteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $criteriaBuilder->addFilter('class_name', $rate->getProductTaxGroup(), 'eq')
            ->addFilter('class_type', ClassModel::TAX_CLASS_TYPE_PRODUCT, 'eq');
        $criteria = $criteriaBuilder->create();

        $taxClassList = $this->taxClassRepository->getList($criteria);

        return $taxClassList;
    }

    public function executeUntilReady()
    {
        for ($i = 0; $i < 3; $i++) {
            $this->cron->execute();

            if ($this->isReady($this->storeManager->getStore()->getId())) {
                break;
            }
        }
    }

    public function isReady($scopeId)
    {
        $cronTaxRules = $this->lsr->getConfigValueFromDb(
            LSR::SC_SUCCESS_CRON_TAX_RULES,
            ScopeInterface::SCOPE_STORES,
            $scopeId
        );
        return $cronTaxRules;
    }

    public function assertCronSuccess($cronConfigs, $storeId, $status = true)
    {
        foreach ($cronConfigs as $config) {
            if (!$status) {
                $this->assertFalse((bool)$this->lsr->getConfigValueFromDb(
                    $config,
                    ScopeInterface::SCOPE_STORES,
                    $storeId
                ));
            } else {
                $this->assertTrue((bool)$this->lsr->getConfigValueFromDb(
                    $config,
                    ScopeInterface::SCOPE_STORES,
                    $storeId
                ));
            }
        }
    }
}
