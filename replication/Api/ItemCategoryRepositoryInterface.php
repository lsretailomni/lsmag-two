<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\ItemCategoryInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface ItemCategoryRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);
    public function save(ItemCategoryInterface $page);
    public function delete(ItemCategoryInterface $page);
    public function getById($id);
    public function deleteById($id);

}

