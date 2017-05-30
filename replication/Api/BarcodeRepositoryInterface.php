<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\BarcodeInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface BarcodeRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);
    public function save(BarcodeInterface $page);
    public function delete(BarcodeInterface $page);
    public function getById($id);
    public function deleteById($id);

}

