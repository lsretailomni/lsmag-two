<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\ReplItemVariantRegistrationInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface ReplItemVariantRegistrationRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);


    public function save(ReplItemVariantRegistrationInterface $page);


    public function delete(ReplItemVariantRegistrationInterface $page);


    public function getById($id);


    public function deleteById($id);



}

