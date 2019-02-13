<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\ReplVendorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface ReplVendorRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);


    public function save(ReplVendorInterface $page);


    public function delete(ReplVendorInterface $page);


    public function getById($id);


    public function deleteById($id);



}

