<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\ReplUnitOfMeasureInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface ReplUnitOfMeasureRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);


    public function save(ReplUnitOfMeasureInterface $page);


    public function delete(ReplUnitOfMeasureInterface $page);


    public function getById($id);


    public function deleteById($id);



}

