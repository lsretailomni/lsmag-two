<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\ReplAttributeOptionValueInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface ReplAttributeOptionValueRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);


    public function save(ReplAttributeOptionValueInterface $page);


    public function delete(ReplAttributeOptionValueInterface $page);


    public function getById($id);


    public function deleteById($id);



}

