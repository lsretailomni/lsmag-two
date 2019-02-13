<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\ReplDiscountValidationInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface ReplDiscountValidationRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);


    public function save(ReplDiscountValidationInterface $page);


    public function delete(ReplDiscountValidationInterface $page);


    public function getById($id);


    public function deleteById($id);



}

