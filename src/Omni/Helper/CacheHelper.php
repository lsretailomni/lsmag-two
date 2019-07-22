<?php

namespace Ls\Omni\Helper;

use Magento\Framework\App\Helper\Context;

/**
 * Class CacheHelper
 * @package Ls\Omni\Helper
 */
class CacheHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    public $cache;

    /**
     * CacheHelper constructor.
     * @param Context $context
     * @param \Magento\Framework\App\CacheInterface $cache
     */
    public function __construct(
        Context $context,
        \Magento\Framework\App\CacheInterface $cache
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
     * @param null $lifetime
     */
    public function persistContentInCache($cacheId, $content, $lifetime = null)
    {
        $serializedContent = json_encode($content);
        $this->cache->save(
            $serializedContent,
            $cacheId,
            [],
            $lifetime
        );
    }
}
