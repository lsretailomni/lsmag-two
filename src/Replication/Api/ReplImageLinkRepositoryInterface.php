<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\ReplImageLinkInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface ReplImageLinkRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);


    public function save(ReplImageLinkInterface $page);


    public function delete(ReplImageLinkInterface $page);


    public function getById($id);


    public function deleteById($id);



}

