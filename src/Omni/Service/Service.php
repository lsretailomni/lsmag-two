<?php

namespace Ls\Omni\Service;

use \Ls\Core\Model\LSR;
use Magento\Framework\App\ObjectManager;
use Laminas\Uri\Uri;
use Laminas\Uri\UriFactory;

class Service
{
    public const DEFAULT_BASE_URL = null; // Default value for base URL

    /** @var LSR $lsr */
    public $lsr;

    /** @var null|string $baseUrl */
    public $baseUrl = null;
    public const CODE_UNIT = 'WS/Codeunit/OmniWrapper';

    // @codingStandardsIgnoreStart
    /**
     * Generates a URI for a specified service type.
     *
     * @param string $baseUrl The base URL to use (optional).
     * @param bool $wsdl Flag to indicate whether to include WSDL (optional).
     *
     * @return Uri The generated URI.
     */
    public static function getUrl(
        $baseUrl = self::DEFAULT_BASE_URL,
        $wsdl = false
    ) {
        // If no base URL is provided, use the default Omni base URL.
        if ($baseUrl == null) {
            // @codingStandardsIgnoreLine
            $baseUrl = (new self())->getOmniBaseUrl();
        }
        $url = $baseUrl;
        if ($wsdl) {
            // Build the full URL by joining the base URL and the corresponding service endpoint.
            $url = join('/', [$baseUrl, self::CODE_UNIT]);
        }
        return UriFactory::factory($url);
    }
    // @codingStandardsIgnoreEnd

    /**
     * Gets the base URL for the Omni service.
     *
     * @param string $magentoStoreId The Magento store ID (optional).
     *
     * @return string The base URL for the Omni service.
     *
     * @see \Ls\Core\Model\LSR::isLSR
     */
    public function getOmniBaseUrl($magentoStoreId = '')
    {
        return 'http://10.213.0.5:9047/LsCentralDev';
        // Initialize the ObjectManager instance
        $objectManager = ObjectManager::getInstance();

        // Create an instance of the LSR model
        // @codingStandardsIgnoreLine
        $lsr = $objectManager->create('Ls\Core\Model\LSR');

        // If no store ID is provided, fetch it from the current store context
        if ($magentoStoreId == '') {
            // Get storeId from the default loaded store.
            $magentoStoreId = $lsr->getCurrentStoreId();
        }

        // Retrieve the base URL from the LSR store configuration
        return $lsr->getStoreConfig(LSR::SC_SERVICE_BASE_URL, $magentoStoreId);
    }
}
