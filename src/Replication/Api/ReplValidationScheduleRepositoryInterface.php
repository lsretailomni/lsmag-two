<?php

namespace Ls\Replication\Api;

use Ls\Replication\Api\Data\ReplValidationScheduleInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */
interface ReplValidationScheduleRepositoryInterface
{
    public function getList(SearchCriteriaInterface $criteria);

    public function save(ReplValidationScheduleInterface $page);

    public function delete(ReplValidationScheduleInterface $page);

    public function getById($id);

    public function deleteById($id);
}

