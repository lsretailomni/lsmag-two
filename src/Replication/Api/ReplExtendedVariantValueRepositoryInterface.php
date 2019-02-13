<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\ReplExtendedVariantValueInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface ReplExtendedVariantValueRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);


    public function save(ReplExtendedVariantValueInterface $page);


    public function delete(ReplExtendedVariantValueInterface $page);


    public function getById($id);


    public function deleteById($id);



}

