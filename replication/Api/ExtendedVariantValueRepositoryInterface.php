<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\ExtendedVariantValueInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface ExtendedVariantValueRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);
    public function save(ExtendedVariantValueInterface $page);
    public function delete(ExtendedVariantValueInterface $page);
    public function getById($id);
    public function deleteById($id);

}

