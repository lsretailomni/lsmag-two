<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\ReplHierarchyInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */
interface ReplHierarchyRepositoryInterface
{
    public function getList(SearchCriteriaInterface $criteria);

    public function save(ReplHierarchyInterface $page);

    public function delete(ReplHierarchyInterface $page);

    public function getById($id);

    public function deleteById($id);
}

