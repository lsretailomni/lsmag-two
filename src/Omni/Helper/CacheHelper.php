<?php

namespace Ls\Omni\Helper;

use \Ls\Omni\Model\Cache\Type;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

/**
 * Class CacheHelper
 * @package Ls\Omni\Helper
 */
class CacheHelper extends AbstractHelper
{
    /**
     * @var Type
     */
    public $cache;

    /**
     * CacheHelper constructor.
     * @param Context $context
     * @param Type $cache
     */
    public function __construct(
        Context $context,
        Type $cache
    ) {
        parent::__construct($context);
        $this->cache = $cache;
    }

    /**
     * @param $cacheId
     * @return bool
     */
    public function getCachedContent($cacheId)
    {
        $cachedContent = $this->cache->load($cacheId);
        if ($cachedContent) {
            return unserialize($cachedContent);
        }

        return false;
    }

    /**
     * @param $cacheId
     * @param $content
     * @param $tag
     * @param null $lifetime
     */
    public function persistContentInCache($cacheId, $content, $tag, $lifetime = null)
    {
        $serializedContent = serialize($content);
        $this->cache->save(
            $serializedContent,
            $cacheId,
            $tag,
            $lifetime
        );
    }
}
