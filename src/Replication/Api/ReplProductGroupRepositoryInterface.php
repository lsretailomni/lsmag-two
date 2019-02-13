<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\ReplProductGroupInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface ReplProductGroupRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);


    public function save(ReplProductGroupInterface $page);


    public function delete(ReplProductGroupInterface $page);


    public function getById($id);


    public function deleteById($id);



}

