<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\ReplDataTranslationInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface ReplDataTranslationRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);


    public function save(ReplDataTranslationInterface $page);


    public function delete(ReplDataTranslationInterface $page);


    public function getById($id);


    public function deleteById($id);



}

