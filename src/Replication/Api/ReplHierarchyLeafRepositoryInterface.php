<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\ReplHierarchyLeafInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */
interface ReplHierarchyLeafRepositoryInterface
{
    public function getList(SearchCriteriaInterface $criteria);

    public function save(ReplHierarchyLeafInterface $page);

    public function delete(ReplHierarchyLeafInterface $page);

    public function getById($id);

    public function deleteById($id);
}

