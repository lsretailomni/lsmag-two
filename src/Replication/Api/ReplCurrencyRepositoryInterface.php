<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\ReplCurrencyInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */
interface ReplCurrencyRepositoryInterface
{
    public function getList(SearchCriteriaInterface $criteria);

    public function save(ReplCurrencyInterface $page);

    public function delete(ReplCurrencyInterface $page);

    public function getById($id);

    public function deleteById($id);
}

