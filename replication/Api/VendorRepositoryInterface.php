<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\VendorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface VendorRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);

    public function save(VendorInterface $page);

    public function delete(VendorInterface $page);

    public function getById($id);

    public function deleteById($id);


}

