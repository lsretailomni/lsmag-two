<?php

namespace Ls\Omni\Plugin\Rule;

use Magento\SalesRule\Model\ResourceModel\Rule\Collection;

/**
 * Interceptor to intercept sales rule collection
 */
class CollectionPlugin
{
    /**
     * Before plugin to fix single store flat_rate error on checkout
     *
     * @param Collection $subject
     * @param $websiteId
     * @param $customerGroupId
     * @param null $now
     * @return array
     */
    public function beforeAddWebsiteGroupDateFilter(
        Collection $subject,
        $websiteId,
        $customerGroupId,
        $now = null
    ) {
        return [(int) $websiteId, $customerGroupId, $now];
    }
}
