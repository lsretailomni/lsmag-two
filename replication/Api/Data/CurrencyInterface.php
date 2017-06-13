<?php

namespace Ls\Replication\Api\Data;

use Ls\Omni\Client\Ecommerce\Entity\Enum\CurrencyRoundingMethod;

interface CurrencyInterface
{

    /**
     * @return CurrencyRoundingMethod
     */
    public function setAmountRoundingMethod($AmountRoundingMethod);
    public function getAmountRoundingMethod();
    /**
     * @return string
     */
    public function setCulture($Culture);
    public function getCulture();
    /**
     * @return int
     */
    public function setDecimalPlaces($DecimalPlaces);
    public function getDecimalPlaces();
    /**
     * @return string
     */
    public function setDecimalSeparator($DecimalSeparator);
    public function getDecimalSeparator();
    /**
     * @return string
     */
    public function setDescription($Description);
    public function getDescription();
    /**
     * @return string
     */
    public function setId($Id);
    public function getId();
    /**
     * @return string
     */
    public function setPostfix($Postfix);
    public function getPostfix();
    /**
     * @return string
     */
    public function setPrefix($Prefix);
    public function getPrefix();
    /**
     * @return float
     */
    public function setRoundOfAmount($RoundOfAmount);
    public function getRoundOfAmount();
    /**
     * @return float
     */
    public function setRoundOffSales($RoundOffSales);
    public function getRoundOffSales();
    /**
     * @return CurrencyRoundingMethod
     */
    public function setSaleRoundingMethod($SaleRoundingMethod);
    public function getSaleRoundingMethod();
    /**
     * @return string
     */
    public function setSymbol($Symbol);
    public function getSymbol();
    /**
     * @return string
     */
    public function setThousandSeparator($ThousandSeparator);
    public function getThousandSeparator();
    /**
     * @return string
     */
    public function setCurrencyCode($CurrencyCode);
    public function getCurrencyCode();
    /**
     * @return string
     */
    public function setCurrencyPrefix($CurrencyPrefix);
    public function getCurrencyPrefix();
    /**
     * @return string
     */
    public function setCurrencySuffix($CurrencySuffix);
    public function getCurrencySuffix();
    /**
     * @return boolean
     */
    public function setDel($Del);
    public function getDel();
    /**
     * @return float
     */
    public function setRoundOfSales($RoundOfSales);
    public function getRoundOfSales();
    /**
     * @return int
     */
    public function setRoundOfTypeAmount($RoundOfTypeAmount);
    public function getRoundOfTypeAmount();
    /**
     * @return int
     */
    public function setRoundOfTypeSales($RoundOfTypeSales);
    public function getRoundOfTypeSales();

}

