<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\AttributeOptionValueInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface AttributeOptionValueRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);
    public function save(AttributeOptionValueInterface $page);
    public function delete(AttributeOptionValueInterface $page);
    public function getById($id);
    public function deleteById($id);

}

