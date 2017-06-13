<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\ImageLinkInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface ImageLinkRepositoryInterface
{

    public function getList(SearchCriteriaInterface $criteria);

    public function save(ImageLinkInterface $page);

    public function delete(ImageLinkInterface $page);

    public function getById($id);

    public function deleteById($id);


}

