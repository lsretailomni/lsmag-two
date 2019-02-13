<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\ReplStoreTenderTypeInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface ReplStoreTenderTypeRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);


    public function save(ReplStoreTenderTypeInterface $page);


    public function delete(ReplStoreTenderTypeInterface $page);


    public function getById($id);


    public function deleteById($id);



}

