<?php

namespace Ls\Replication\Block\Adminhtml\Grid\Renderer\Action;

/**
 * Class UrlBuilder
 * @package Ls\Replication\Block\Adminhtml\Grid\Renderer\Action
 */
class UrlBuilder
{
    /**
     * @var \Magento\Framework\UrlInterface
     */
    public $frontendUrlBuilder;

    /**
     * @param \Magento\Framework\UrlInterface $frontendUrlBuilder
     */
    public function __construct(\Magento\Framework\UrlInterface $frontendUrlBuilder)
    {
        $this->frontendUrlBuilder = $frontendUrlBuilder;
    }

    /**
     * Get action url
     *
     * @param string $routePath
     * @param string $scope
     * @param string $store
     * @return string
     */
    public function getUrl($routePath, $scope, $store)
    {
        $this->frontendUrlBuilder->setScope($scope);
        $href = $this->frontendUrlBuilder->getUrl(
            $routePath,
            ['_current' => false, '_query' => '___store=' . $store]
        );
        return $href;
    }
}
