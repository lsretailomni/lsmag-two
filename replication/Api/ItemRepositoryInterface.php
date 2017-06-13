<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\ItemInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface ItemRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);

    public function save(ItemInterface $page);

    public function delete(ItemInterface $page);

    public function getById($id);

    public function deleteById($id);


}

