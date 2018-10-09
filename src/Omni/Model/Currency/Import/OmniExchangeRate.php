<?php

namespace Ls\Omni\Model\Currency\Import;

class OmniExchangeRate extends \Magento\Directory\Model\Currency\Import\AbstractImport
{

    /** @var string  */
    private $currencyConverterUrl = 'http://query.yahooapis.com/v1/public/yql?format=json&q={{YQL_STRING}}&env=store://datatables.org/alltableswithkeys';

    // @codingStandardsIgnoreEnd
    private $timeoutConfigPath = 'currency/yahoofinance/timeout';

    /** @var \Magento\Framework\HTTP\ZendClientFactory  */
    protected $httpClientFactory;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface  */
    private $scopeConfig;

    /** @var \Ls\Replication\Model\ReplCurrencyExchRateRepository  */
    private $repository;

    /**
     * OmniExchangeRate constructor.
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory
     * @param \Ls\Replication\Model\ReplCurrencyExchRateRepository $repository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder
     */
    public function __construct(
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory,
        \Ls\Replication\Model\ReplCurrencyExchRateRepository $repository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder
    ) {
        parent::__construct($currencyFactory);

        $this->scopeConfig = $scopeConfig;
        $this->httpClientFactory = $httpClientFactory;
        $this->repository = $repository;

        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
    }

    public function fetchRates()
    {

        $data = [];
        $currencies = $this->_getCurrencyCodes();
        $defaultCurrencies = $this->_getDefaultCurrencyCodes();

        foreach ($defaultCurrencies as $currencyFrom) {
            if (!isset($data[$currencyFrom])) {
                $data[$currencyFrom] = [];
            }
            $data = $this->convertBatch($data, $currencyFrom, $currencies);
            ksort($data[$currencyFrom]);
        }

        return $data;
    }

    /**
     * Return currencies convert rates in batch mode
     *
     * @param array $data
     * @param string $currencyFrom
     * @param array $currenciesTo
     * @return array
     */
    private function convertBatch($data, $currencyFrom, $currenciesTo)
    {
        foreach ($currenciesTo as $currencyTo) {
            if ($currencyFrom == $currencyTo) {
                $data[$currencyFrom][$currencyTo] = $this->_numberFormat(1);
            } else {

                $filter{$currencyTo} = $this->filterBuilder->setField('CurrencyCode')
                    ->setValue($currencyTo)
                    ->setConditionType('eq')
                    ->create();

                $filterOr = $this->filterGroupBuilder
                    ->addFilter($filter{$currencyTo})
                    ->create();
                $this->searchCriteriaBuilder->setFilterGroups([$filterOr]);

                $searchCriteria = $this->searchCriteriaBuilder->create();

                $results = $this->repository->getList($searchCriteria);

                $data[$currencyFrom][$currencyTo] = $this->_numberFormat(
                    (double) 1 + (1 * $results->getItems()[0]->getData()["CurrencyFactor"])
                );

                // todo Fix the calculation maybe ?
            }
        }

        return $data;
    }

    /**
     * Retrieve rate
     *
     * @param   string $currencyFrom
     * @param   string $currencyTo
     * @return  float
     */
    protected function _convert($currencyFrom, $currencyTo)
    {

    }
}
