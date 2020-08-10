<?php

namespace Ls\Omni\Model\Order\Pdf;

use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Sales\Model\AbstractModel;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Sales\Model\Order\Pdf\Config;
use Magento\Sales\Model\Order\Pdf\ItemsFactory;
use Magento\Sales\Model\Order\Pdf\Total\Factory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Zend_Pdf;use Zend_Pdf_Color_GrayScale;
use Zend_Pdf_Color_Rgb;
use Zend_Pdf_Exception;
use Zend_Pdf_Page;
use Zend_Pdf_Style;

/**
 * Class Invoice
 * @package Ls\Omni\Model\Order\Pdf
 */
class Invoice extends \Magento\Sales\Model\Order\Pdf\Invoice
{
    /**
     * @param Zend_Pdf_Page $page
     * @throws LocalizedException
     */

    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var ResolverInterface
     */
    public $localeResolver;

    /**
     * \Ls\Omni\Helper\LoyaltyHelper
     */
    public $loyaltyHelper;

    /**
     * @var Data
     */
    public $priceHelper;

    /**
     * Invoice constructor.
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param StringUtils $string
     * @param ScopeConfigInterface $scopeConfig
     * @param Filesystem $filesystem
     * @param Config $pdfConfig
     * @param Factory $pdfTotalFactory
     * @param ItemsFactory $pdfItemsFactory
     * @param TimezoneInterface $localeDate
     * @param StateInterface $inlineTranslation
     * @param Renderer $addressRenderer
     * @param StoreManagerInterface $storeManager
     * @param ResolverInterface $localeResolver
     * @param LoyaltyHelper $loyaltyHelper
     * @param Data $priceHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Payment\Helper\Data $paymentData,
        StringUtils $string,
        ScopeConfigInterface $scopeConfig,
        Filesystem $filesystem,
        Config $pdfConfig,
        Factory $pdfTotalFactory,
        ItemsFactory $pdfItemsFactory,
        TimezoneInterface $localeDate,
        StateInterface $inlineTranslation,
        Renderer $addressRenderer,
        StoreManagerInterface $storeManager,
        ResolverInterface $localeResolver,
        LoyaltyHelper $loyaltyHelper,
        Data $priceHelper,
        array $data = []
    ) {
        $this->storeManager   = $storeManager;
        $this->localeResolver = $localeResolver;
        $this->loyaltyHelper  = $loyaltyHelper;
        $this->priceHelper    = $priceHelper;
        parent::__construct(
            $paymentData,
            $string,
            $scopeConfig,
            $filesystem,
            $pdfConfig,
            $pdfTotalFactory,
            $pdfItemsFactory,
            $localeDate,
            $inlineTranslation,
            $addressRenderer,
            $storeManager,
            $localeResolver,
            $data
        );
    }

    /**
     * @param Zend_Pdf_Page $page
     * @throws LocalizedException
     */
    public function _drawHeader(Zend_Pdf_Page $page)
    {
        /* Add table head */
        $this->_setFontRegular($page, 10);
        // @codingStandardsIgnoreLine
        $page->setFillColor(new Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
        // @codingStandardsIgnoreLine
        $page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineWidth(0.5);
        $page->drawRectangle(25, $this->y, 570, $this->y - 15);
        $this->y -= 10;
        // @codingStandardsIgnoreLine
        $page->setFillColor(new Zend_Pdf_Color_Rgb(0, 0, 0));

        //columns headers
        $lines[0][] = ['text' => __('Products'), 'feed' => 35];

        $lines[0][] = ['text' => __('SKU'), 'feed' => 290, 'align' => 'right'];

        $lines[0][] = ['text' => __('Qty'), 'feed' => 435, 'align' => 'right'];

        $lines[0][] = ['text' => __('Price'), 'feed' => 375, 'align' => 'right'];

        $lines[0][] = ['text' => __('Discount'), 'feed' => 495, 'align' => 'right'];

        $lines[0][] = ['text' => __('Subtotal'), 'feed' => 565, 'align' => 'right'];

        $lineBlock = ['lines' => $lines, 'height' => 5];

        $this->drawLineBlocks($page, [$lineBlock], ['table_header' => true]);
        // @codingStandardsIgnoreLine
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $this->y -= 20;
    }

    /**
     * @param Zend_Pdf_Page $page
     * @param AbstractModel $source
     * @return Zend_Pdf_Page
     * @throws LocalizedException
     */
    public function insertTotals($page, $source)
    {
        $order     = $source->getOrder();
        $totals    = $this->_getTotalsList();
        $lineBlock = ['lines' => [], 'height' => 15];
        foreach ($totals as $total) {
            $total->setOrder($order)->setSource($source);

            if ($total->canDisplay()) {
                $total->setFontSize(10);
                foreach ($total->getTotalsForDisplay() as $totalData) {
                    if (trim(str_replace("():", "", $totalData['label'])) == "Discount") {
                        $totalData['label'] = __("Total Discount:");
                        if ($order->getLsPointsSpent() > 0) {
                            $loyaltyAmount        = $order->getLsPointsSpent() * $this->loyaltyHelper->getPointRate();
                            $loyaltyAmount        = $this->priceHelper->currency($loyaltyAmount, true, false);
                            $lineBlock['lines'][] = [
                                [
                                    'text'      => __("Loyalty Points Redeemed:"),
                                    'feed'      => 475,
                                    'align'     => 'right',
                                    'font_size' => $totalData['font_size'],
                                    'font'      => 'bold',
                                ],
                                [
                                    'text'      => $loyaltyAmount,
                                    'feed'      => 565,
                                    'align'     => 'right',
                                    'font_size' => $totalData['font_size'],
                                    'font'      => 'bold'
                                ],
                            ];
                        }

                        if ($order->getLsGiftCardAmountUsed() > 0) {
                            $giftCardAmount       = $this->priceHelper->currency(
                                $order->getLsGiftCardAmountUsed(),
                                true,
                                false
                            );
                            $lineBlock['lines'][] = [
                                [
                                    'text'      => __("GiftCard Redeemed ") . '(' . $order->getLsGiftCardNo() . '):' . "",
                                    'feed'      => 475,
                                    'align'     => 'right',
                                    'font_size' => $totalData['font_size'],
                                    'font'      => 'bold',
                                ],
                                [
                                    'text'      => $giftCardAmount,
                                    'feed'      => 565,
                                    'align'     => 'right',
                                    'font_size' => $totalData['font_size'],
                                    'font'      => 'bold'
                                ],
                            ];
                        }
                    }
                    $lineBlock['lines'][] = [
                        [
                            'text'      => $totalData['label'],
                            'feed'      => 475,
                            'align'     => 'right',
                            'font_size' => $totalData['font_size'],
                            'font'      => 'bold',
                        ],
                        [
                            'text'      => $totalData['amount'],
                            'feed'      => 565,
                            'align'     => 'right',
                            'font_size' => $totalData['font_size'],
                            'font'      => 'bold'
                        ],
                    ];
                }
            }
        }

        $this->y -= 20;
        $page    = $this->drawLineBlocks($page, [$lineBlock]);
        return $page;
    }

    /**
     * @param array $invoices
     * @return Zend_Pdf
     * @throws LocalizedException
     * @throws Zend_Pdf_Exception
     */
    public function getPdf($invoices = [])
    {
        $this->_beforeGetPdf();
        $this->_initRenderer('invoice');
        // @codingStandardsIgnoreLine
        $pdf = new Zend_Pdf();
        $this->_setPdf($pdf);
        // @codingStandardsIgnoreLine
        $style = new Zend_Pdf_Style();
        $this->_setFontBold($style, 10);

        foreach ($invoices as $invoice) {
            if ($invoice->getStoreId()) {
                $this->_localeResolver->emulate($invoice->getStoreId());
                $this->_storeManager->setCurrentStore($invoice->getStoreId());
            }
            $page  = $this->newPage();
            $order = $invoice->getOrder();
            if (!empty($order->getDocumentId())) {
                $order->setIncrementId($order->getDocumentId());
            }
            /* Add image */
            $this->insertLogo($page, $invoice->getStore());
            /* Add address */
            $this->insertAddress($page, $invoice->getStore());
            /* Add head */
            $this->insertOrder(
                $page,
                $order,
                $this->_scopeConfig->isSetFlag(
                    self::XML_PATH_SALES_PDF_INVOICE_PUT_ORDER_ID,
                    ScopeInterface::SCOPE_STORE,
                    $order->getStoreId()
                )
            );
            /* Add document text and number */
            $this->insertDocumentNumber($page, __('Invoice # ') . $invoice->getIncrementId());
            /* Add table */
            $this->_drawHeader($page);
            /* Add body */
            foreach ($invoice->getAllItems() as $item) {
                if ($item->getOrderItem()->getParentItem()) {
                    continue;
                }
                /* Draw item */
                $this->_drawItem($item, $page, $order);
                $page = end($pdf->pages);
            }
            /* Add totals */
            $this->insertTotals($page, $invoice);
            if ($invoice->getStoreId()) {
                $this->_localeResolver->revert();
            }
        }
        $this->_afterGetPdf();
        return $pdf;
    }
}
