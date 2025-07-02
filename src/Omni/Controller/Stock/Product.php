<?php
declare(strict_types=1);

namespace Ls\Omni\Controller\Stock;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Omni\Block\Stores\Stores;
use \Ls\Omni\Helper\StockHelper;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
use Laminas\Json\Json as LaminasJson;

/**
 * Controller to check given item availability in all stores
 */
class Product implements HttpPostActionInterface
{
    /**
     * @param JsonFactory $resultJsonFactory
     * @param PageFactory $resultPageFactory
     * @param Http $request
     * @param StockHelper $stockHelper
     */
    public function __construct(
        public JsonFactory $resultJsonFactory,
        public PageFactory $resultPageFactory,
        public RequestInterface $request,
        public StockHelper $stockHelper
    ) {
    }

    /**
     * Controller responsible for providing given item stock in all stores
     *
     * @return ResponseInterface|Json|ResultInterface
     * @throws NoSuchEntityException
     * @throws GuzzleException
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $resultPage = $this->resultPageFactory->create();
        // @codingStandardsIgnoreStart
        $notAvailableNoticeTitle = __("Notice");
        $notAvailableNoticeContent = __("This item is only available online.");
        // @codingStandardsIgnoreEnd
        if ($this->request->isAjax()) {
            $productSku = $this->request->getParam('sku');
            $simpleProductId = $this->request->getParam('id');

            $customResponse = $this->stockHelper->fetchAllStoresItemInStockPlusApplyJoin($simpleProductId, $productSku);

            $storesData = $resultPage->getLayout()->createBlock(Stores::class)
                ->setTemplate('Ls_Omni::stores/stores.phtml')
                ->setData('data', $customResponse)
                ->toHtml();

            $stores = $customResponse->toArray();
            $stores['storesInfo'] = $storesData;
            $encodedStores = LaminasJson::encode($stores);

            $result = $result->setData(
                [
                    'title' => $notAvailableNoticeTitle,
                    'content' => $notAvailableNoticeContent,
                    'stocks' => $encodedStores,
                ]
            );
        }
        return $result;
    }
}
