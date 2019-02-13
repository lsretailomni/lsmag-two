<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\ReplPriceInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface ReplPriceRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);


    public function save(ReplPriceInterface $page);


    public function delete(ReplPriceInterface $page);


    public function getById($id);


    public function deleteById($id);



}

