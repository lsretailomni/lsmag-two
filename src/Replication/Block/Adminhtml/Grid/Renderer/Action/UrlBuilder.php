<?php

namespace Ls\Replication\Block\Adminhtml\Grid\Renderer\Action;

use Magento\Framework\UrlInterface;

/**
 * Class UrlBuilder
 * @package Ls\Replication\Block\Adminhtml\Grid\Renderer\Action
 */
class UrlBuilder
{
    /**
     * @var UrlInterface
     */
    public $frontendUrlBuilder;

    /**
     * @param UrlInterface $frontendUrlBuilder
     */
    public function __construct(UrlInterface $frontendUrlBuilder)
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
        return $this->frontendUrlBuilder->getUrl(
            $routePath,
            ['_current' => false, '_query' => '___store=' . $store]
        );
    }
}
