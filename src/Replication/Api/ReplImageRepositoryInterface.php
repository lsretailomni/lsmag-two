<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\ReplImageInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface ReplImageRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);


    public function save(ReplImageInterface $page);


    public function delete(ReplImageInterface $page);


    public function getById($id);


    public function deleteById($id);



}

