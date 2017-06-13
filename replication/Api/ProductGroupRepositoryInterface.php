<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\ProductGroupInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface ProductGroupRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);

    public function save(ProductGroupInterface $page);

    public function delete(ProductGroupInterface $page);

    public function getById($id);

    public function deleteById($id);


}

