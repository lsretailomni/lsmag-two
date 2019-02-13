<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\LoyItemInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface LoyItemRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);


    public function save(LoyItemInterface $page);


    public function delete(LoyItemInterface $page);


    public function getById($id);


    public function deleteById($id);



}

