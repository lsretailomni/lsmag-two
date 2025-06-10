<?php

namespace Ls\Omni\Helper;

use \Ls\Omni\Model\Cache\Type;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\State;
use Magento\Framework\DataObject;

/**
 * For implementing magento cache and wsdl cache options
 */
class CacheHelper extends AbstractHelper
{
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
        public Context $context,
        public Type $cache,
        public State $state
    ) {
        parent::__construct($context);
    }

    /**
     * Get cached content
     *
     * @param string $cacheId
     * @return DataObject|boolean
     */
    public function getCachedContent(string $cacheId)
    {
        $cachedContent = $this->cache->load($cacheId);
        if ($cachedContent) {
            // phpcs:disable Magento2.Security.InsecureFunction.FoundWithAlternative
            $cached = unserialize($cachedContent);
            if (!is_array($cached)) {
                return $cached;
            }
            return $this->restoreModel($cached);
        }

        return false;
    }

    /**
     * Remove cached content
     *
     * @param string $cacheId
     * @return bool
     */
    public function removeCachedContent(string $cacheId)
    {
        return $this->cache->remove($cacheId);
    }

    /**
     * Persist content in the cache
     *
     * @param string $cacheId
     * @param mixed $content
     * @param array $tag
     * @param int|null $lifetime
     * @return void
     */
    public function persistContentInCache(string $cacheId, $content, array $tag, ?int $lifetime = null)
    {
        $flattened = $content instanceof DataObject ? $this->flattenModel($content) : $content;
        // phpcs:disable Magento2.Security.InsecureFunction.FoundWithAlternative
        $serializedContent = serialize($flattened);
        $this->cache->save(
            $serializedContent,
            $cacheId,
            $tag,
            $lifetime
        );
    }

    /**
     * Return Wsdl cache parameter based on mode
     *
     * @return array
     */
    public function getWsdlOptions()
    {
        if (!$this->state || $this->state->getMode() == State::MODE_DEVELOPER) {
            $this->soapOptions['cache_wsdl'] = WSDL_CACHE_NONE;
        }
        return $this->soapOptions;
    }

    /**
     * Flat the given model into serializable array
     *
     * @param DataObject $model
     * @return array
     */
    public function flattenModel(DataObject $model): array
    {
        $data = $model->getData();

        foreach ($data as $key => $value) {
            // Handle nested model
            if ($value instanceof DataObject) {
                $data[$key] = [
                    '__is_model__' => true,
                    '__class__' => get_class($value),
                    'data' => $this->flattenModel($value),
                ];
            } elseif (is_array($value)) {
                $data[$key] = array_map(function ($item) {
                    if ($item instanceof DataObject) {
                        return [
                            '__is_model__' => true,
                            '__class__' => get_class($item),
                            'data' => $this->flattenModel($item),
                        ];
                    }
                    return $item;
                }, $value);
            }
        }

        return [
            '__class__' => get_class($model),
            'data' => $data
        ];
    }

    /**
     * Restore a model from a serialized array
     *
     * @param array $structure
     * @return DataObject
     */
    public function restoreModel(array $structure): DataObject
    {
        $class = $structure['__class__'];
        $rawData = $structure['data'];

        foreach ($rawData as $key => $value) {
            // Handle single nested model
            if (is_array($value) && isset($value['__is_model__'])) {
                $rawData[$key] = $this->restoreModel($value);
            } elseif (is_array($value)) {
                $rawData[$key] = array_map(function ($item) {
                    if (is_array($item) && isset($item['__is_model__'])) {
                        return $this->restoreModel($item);
                    }
                    return $item;
                }, $value);
            }
        }

        /** @var DataObject $model */
        $model = \Magento\Framework\App\ObjectManager::getInstance()->create($class);
        $model->setData($rawData['data'] ?? $rawData);

        return $model;
    }
}
