<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\VendorItemMappingInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface VendorItemMappingRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);
    public function save(VendorItemMappingInterface $page);
    public function delete(VendorItemMappingInterface $page);
    public function getById($id);
    public function deleteById($id);

}

