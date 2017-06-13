<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\ImageInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface ImageRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);

    public function save(ImageInterface $page);

    public function delete(ImageInterface $page);

    public function getById($id);

    public function deleteById($id);


}

