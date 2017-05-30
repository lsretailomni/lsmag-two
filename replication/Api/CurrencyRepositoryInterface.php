<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\CurrencyInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface CurrencyRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);
    public function save(CurrencyInterface $page);
    public function delete(CurrencyInterface $page);
    public function getById($id);
    public function deleteById($id);

}

