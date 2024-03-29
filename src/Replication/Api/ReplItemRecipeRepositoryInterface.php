<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\ReplItemRecipeInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */
interface ReplItemRecipeRepositoryInterface
{
    public function getList(SearchCriteriaInterface $criteria);

    public function save(ReplItemRecipeInterface $page);

    public function delete(ReplItemRecipeInterface $page);

    public function getById($id);

    public function deleteById($id);
}

