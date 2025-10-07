<?php
declare(strict_types=1);

namespace Ls\Replication\Plugin\Admin;

use \Ls\Core\Model\LSR;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Plugin for admin menu
 */
class Config
{
    /**
     * @param LSR $lsr
     */
    public function __construct(
        public LSR $lsr
    ) {
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
            }
        }

        return $result;
    }
}
