<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\ReplCurrencyExchRateInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface ReplCurrencyExchRateRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);


    public function save(ReplCurrencyExchRateInterface $page);


    public function delete(ReplCurrencyExchRateInterface $page);


    public function getById($id);


    public function deleteById($id);



}

