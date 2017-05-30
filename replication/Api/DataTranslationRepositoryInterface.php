<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\DataTranslationInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface DataTranslationRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);
    public function save(DataTranslationInterface $page);
    public function delete(DataTranslationInterface $page);
    public function getById($id);
    public function deleteById($id);

}

