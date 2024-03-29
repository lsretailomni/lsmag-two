<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\ReplLoyVendorItemMappingInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */
interface ReplLoyVendorItemMappingRepositoryInterface
{
    public function getList(SearchCriteriaInterface $criteria);

    public function save(ReplLoyVendorItemMappingInterface $page);

    public function delete(ReplLoyVendorItemMappingInterface $page);

    public function getById($id);

    public function deleteById($id);
}

