<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\StoreInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface StoreRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);

    public function save(StoreInterface $page);

    public function delete(StoreInterface $page);

    public function getById($id);

    public function deleteById($id);


}

