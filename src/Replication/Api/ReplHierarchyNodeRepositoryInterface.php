<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\ReplHierarchyNodeInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface ReplHierarchyNodeRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);


    public function save(ReplHierarchyNodeInterface $page);


    public function delete(ReplHierarchyNodeInterface $page);


    public function getById($id);


    public function deleteById($id);



}

