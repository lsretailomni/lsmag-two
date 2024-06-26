<?php

namespace Ls\Omni\Plugin\Model\Cache;

use \Ls\Core\Model\LSR;
use Magento\PageCache\Model\Cache\Type;

class TypePlugin
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
     * @param Type $subject
     * @param $proceed
     * @param $mode
     * @param array $tags
     * @return mixed|true
     */
    public function aroundClean(
        Type $subject,
        $proceed,
        $mode = \Zend_Cache::CLEANING_MODE_ALL,
        array $tags = []
    ) {
        if ($subject->getTag() == Type::CACHE_TAG &&
            $mode == \Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG &&
            !empty($tags) &&
            $this->lsr->getStopFpcPurge($tags)
        ) {
            return true;
        }

        return $proceed($mode, $tags);
    }
}
