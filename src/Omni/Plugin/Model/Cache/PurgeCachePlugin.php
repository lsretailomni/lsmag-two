<?php
declare(strict_types=1);

namespace Ls\Omni\Plugin\Model\Cache;

use \Ls\Core\Model\LSR;
use Magento\CacheInvalidate\Model\PurgeCache;

class PurgeCachePlugin
{
    /**
     * @param LSR $lsr
     */
    public function __construct(public LSR $lsr)
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
