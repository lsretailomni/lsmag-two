<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\ReplCustomerInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface ReplCustomerRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);


    public function save(ReplCustomerInterface $page);


    public function delete(ReplCustomerInterface $page);


    public function getById($id);


    public function deleteById($id);



}

