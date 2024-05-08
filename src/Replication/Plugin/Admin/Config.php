<?php

namespace Ls\Replication\Plugin\Admin;

use \Ls\Core\Model\LSR;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Plugin for admin menu
 */
class Config
{

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * @param LSR $lsr
     */
    public function __construct(
        LSR $lsr
    ) {
        $this->lsr = $lsr;
    }

    /**
     * Remove particular admin menu
     *
     * @param $subject
     * @param $result
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function afterGetMenu($subject, $result)
    {
        if (!empty($this->lsr->getOmniVersion())) {
            if (version_compare($this->lsr->getOmniVersion(), '2024.4.0', '>=')) {
                $result->remove('Ls_Replication::discount_grid');
            } else {
                $result->remove('Ls_Replication::discount_setup_grid');
                $result->remove('Ls_Replication::discount_validation_grid');
            }
        }

        return $result;
    }
}
