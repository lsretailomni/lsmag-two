<?php

namespace Ls\CommerceCloud\Model;

/**
 * CommerceCloud LSR
 */
class LSR extends \Ls\Core\Model\LSR
{
    const SC_REPLICATION_CENTRAL_TYPE = 'ls_mag/service/central_type';

    /**
     * Check to see if same app_id exists already
     *
     * @param $value
     * @return bool
     */
    public function configValueExists($value)
    {
        $configDataCollection = $this->configDataCollectionFactory->create();
        $configDataCollection
            ->addFieldToFilter('value', $value)
            ->addFieldToFilter('path', ['like' => 'ls_mag/replication/app_id_%']);

        if ($configDataCollection->count() !== 0) {
            return true;
        }

        return false;
    }
}
