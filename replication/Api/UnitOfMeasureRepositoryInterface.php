<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\UnitOfMeasureInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface UnitOfMeasureRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);
    public function save(UnitOfMeasureInterface $page);
    public function delete(UnitOfMeasureInterface $page);
    public function getById($id);
    public function deleteById($id);

}

