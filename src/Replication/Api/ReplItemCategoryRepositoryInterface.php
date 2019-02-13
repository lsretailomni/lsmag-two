<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\ReplItemCategoryInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface ReplItemCategoryRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);


    public function save(ReplItemCategoryInterface $page);


    public function delete(ReplItemCategoryInterface $page);


    public function getById($id);


    public function deleteById($id);



}

