<?php

namespace Ls\Omni\Plugin\Model\Cache;

use \Ls\Core\Model\LSR;
use Magento\CacheInvalidate\Model\PurgeCache;

class PurgeCachePlugin
{
    /**
     * @var LSR
     */
    public $lsr;

    /**
     * @param LSR $lsr
     */
    public function __construct(LSR $lsr)
    {
        $this->lsr = $lsr;
    }

    /**
     * Around plugin to intercept the cache clean
     *
     * @param PurgeCache $subject
     * @param $proceed
     * @param $tags
     * @return mixed|true
     */
    public function aroundSendPurgeRequest(
        PurgeCache $subject,
        $proceed,
        $tags
    ) {
        if (!empty($tags) &&
            $this->lsr->getStopFpcPurge($tags)
        ) {
            return true;
        }

        return $proceed($tags);
    }
}
