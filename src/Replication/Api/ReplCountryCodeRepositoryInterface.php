<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\ReplCountryCodeInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface ReplCountryCodeRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);


    public function save(ReplCountryCodeInterface $page);


    public function delete(ReplCountryCodeInterface $page);


    public function getById($id);


    public function deleteById($id);



}

