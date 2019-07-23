<?php

namespace Ls\Omni\Helper;

use Magento\Framework\App\Helper\Context;
use \Ls\Omni\Model\Cache\Type;

/**
 * Class CacheHelper
 * @package Ls\Omni\Helper
 */
class CacheHelper extends \Magento\Framework\App\Helper\AbstractHelper
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
            return json_decode($cachedContent, true);
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
        $serializedContent = json_encode($content);
        $this->cache->save(
            $serializedContent,
            $cacheId,
            $tag,
            $lifetime
        );
    }
}
