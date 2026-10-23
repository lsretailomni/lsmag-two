<?php
declare(strict_types=1);

namespace Ls\Omni\Controller\Ajax;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Block\Product\View\VendorSourcing\Options;
use \Ls\Omni\Helper\VendorSourcingHelper;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;

/**
 * Returns the vendor sourcing (order from vendor) options of a product as an HTML fragment
 */
class VendorSourcingOptions implements HttpGetActionInterface
{
    private const MAX_SKU_LENGTH = 64;

    /**
     * @param RequestInterface $request
     * @param JsonFactory $resultJsonFactory
     * @param PageFactory $resultPageFactory
     * @param VendorSourcingHelper $vendorSourcingHelper
     * @param LSR $lsr
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $resultJsonFactory,
        private readonly PageFactory $resultPageFactory,
        private readonly VendorSourcingHelper $vendorSourcingHelper,
        private readonly LSR $lsr,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Entry point for this controller
     *
     * @return Json
     */
    public function execute()
    {
        if (!$this->request->isXmlHttpRequest()) {
            return $this->respond(false);
        }

        $sku = $this->request->getParam('sku');
        $id = $this->request->getParam('id');

        if (!$this->lsr->getStoreConfig(LSR::SC_CART_VENDOR_SOURCING_OPTIONS, $this->lsr->getCurrentStoreId()) ||
            !is_string($sku) || $sku === '' || strlen($sku) > self::MAX_SKU_LENGTH
        ) {
            return $this->respond(false);
        }

        $simpleProductId = null;

        if ($id !== null && $id !== '') {
            if (!is_string($id) || !ctype_digit($id) || (int)$id <= 0) {
                return $this->respond(false);
            }
            $simpleProductId = (int)$id;
        }

        try {
            $options = $this->vendorSourcingHelper->getOptions($sku, $simpleProductId);
        } catch (NoSuchEntityException $e) {
            return $this->respond(false);
        } catch (\Throwable $e) {
            $this->logger->error('Vendor sourcing options failed: ' . $e->getMessage());

            return $this->respond(false);
        }

        if (empty($options)) {
            return $this->respond(false);
        }

        $output = $this->resultPageFactory->create()->getLayout()
            ->createBlock(Options::class)
            ->setTemplate('Ls_Omni::product/view/vendorsourcing/options.phtml')
            ->setData('options', $options)
            ->toHtml();

        return $this->respond(true, $output);
    }

    /**
     * Build the JSON response
     *
     * @param bool $success
     * @param string $output
     * @return Json
     */
    private function respond(bool $success, string $output = ''): Json
    {
        $message = $success ? '' : (string)__(
            'Delivery and collection options are currently not available. Please try again later.'
        );

        return $this->resultJsonFactory->create()->setData(
            ['success' => $success, 'output' => $output, 'message' => $message]
        );
    }
}
