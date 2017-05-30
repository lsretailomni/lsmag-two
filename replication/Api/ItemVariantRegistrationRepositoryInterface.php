<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\ItemVariantRegistrationInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface ItemVariantRegistrationRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);
    public function save(ItemVariantRegistrationInterface $page);
    public function delete(ItemVariantRegistrationInterface $page);
    public function getById($id);
    public function deleteById($id);

}

