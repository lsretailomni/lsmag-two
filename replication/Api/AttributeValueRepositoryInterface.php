<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\AttributeValueInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface AttributeValueRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);

    public function save(AttributeValueInterface $page);

    public function delete(AttributeValueInterface $page);

    public function getById($id);

    public function deleteById($id);


}

