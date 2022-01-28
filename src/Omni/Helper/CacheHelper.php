<?php

namespace Ls\Omni\Helper;

use \Ls\Omni\Model\Cache\Type;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\State;

/**
 * For implementing magento cache and wsdl cache options
 */
class CacheHelper extends AbstractHelper
{
    /**
     * @var Type
     */
    public $cache;

    /**
     * @var State
     */
    public $state;

    /** @var array */
    public $soapOptions = [
        'cache_wsdl'   => WSDL_CACHE_DISK,
        'soap_version' => SOAP_1_1,
        'features'     => SOAP_SINGLE_ELEMENT_ARRAYS
    ];

    /**
     * @param Context $context
     * @param Type $cache
     * @param State $state
     */
    public function __construct(
        Context $context,
        Type $cache,
        State $state
    ) {
        parent::__construct($context);
        $this->cache = $cache;
        $this->state = $state;
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

    /**
     * Return Wsdl cache parameter based on mode
     * @return array
     */
    public function getWsdlOptions()
    {
        if ($this->state->getMode() == State::MODE_DEVELOPER) {
            $this->soapOptions['cache_wsdl'] = WSDL_CACHE_NONE;
        }
        return $this->soapOptions;
    }
}
