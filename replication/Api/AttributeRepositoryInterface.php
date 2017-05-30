<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\AttributeInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface AttributeRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);
    public function save(AttributeInterface $page);
    public function delete(AttributeInterface $page);
    public function getById($id);
    public function deleteById($id);

}

