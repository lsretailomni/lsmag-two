<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\ItemUOMInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface ItemUOMRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);

    public function save(ItemUOMInterface $page);

    public function delete(ItemUOMInterface $page);

    public function getById($id);

    public function deleteById($id);


}

