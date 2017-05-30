<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\PriceInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface PriceRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);
    public function save(PriceInterface $page);
    public function delete(PriceInterface $page);
    public function getById($id);
    public function deleteById($id);

}

