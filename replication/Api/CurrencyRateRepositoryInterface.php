<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\CurrencyRateInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface CurrencyRateRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);
    public function save(CurrencyRateInterface $page);
    public function delete(CurrencyRateInterface $page);
    public function getById($id);
    public function deleteById($id);

}

