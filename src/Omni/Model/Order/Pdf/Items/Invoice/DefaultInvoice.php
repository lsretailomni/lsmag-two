<?php

namespace Ls\Omni\Model\Order\Pdf\Items\Invoice;

/**
 * Class DefaultInvoice
 * @package Ls\Omni\Model\Order\Pdf\Items\Invoice
 */
class DefaultInvoice extends \Magento\Sales\Model\Order\Pdf\Items\Invoice\DefaultInvoice
{
    /**
     * Core string
     *
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    public $string;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    public $productRepository;

    /**
     * DefaultInvoice constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Tax\Helper\Data $taxData
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Filter\FilterManager $filterManager
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Tax\Helper\Data $taxData,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filter\FilterManager $filterManager,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->string = $string;
        $this->productRepository = $productRepository;
        parent::__construct(
            $context,
            $registry,
            $taxData,
            $filesystem,
            $filterManager,
            $string,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function draw()
    {
        $order = $this->getOrder();
        $item = $this->getItem();
        $pdf = $this->getPdf();
        $page = $this->getPage();
        $lines = [];
        $id = $item->getProductid();
        $product = $this->productRepository->getById($id);
        $PcbMaster = $product->getData('pcb_master');

        // draw Product name
        $lines[0] = [['text' => $this->string->split($item->getName(), 35, true, true), 'feed' => 35]];

        $lines[0][] = [
            'text' => $PcbMaster,
            'feed' => 190,
            'align' => 'right',
        ];

        // draw SKU
        $lines[0][] = [
            'text' => $this->string->split($this->getSku($item), 17),
            'feed' => 290,
            'align' => 'right',
        ];

        // draw QTY
        $lines[0][] = ['text' => $item->getQty() * 1, 'feed' => 435, 'align' => 'right'];

        // draw item Prices
        $i = 0;
        $prices = $this->getItemPricesForDisplay();
        $feedPrice = 395;
        $feedSubtotal = $feedPrice + 170;
        foreach ($prices as $priceData) {
            if (isset($priceData['label'])) {
                // draw Price label
                $lines[$i][] = ['text' => $priceData['label'], 'feed' => $feedPrice, 'align' => 'right'];
                // draw Subtotal label
                $lines[$i][] = ['text' => $priceData['label'], 'feed' => $feedSubtotal, 'align' => 'right'];
                $i++;
            }
            // draw Price
            $lines[$i][] = [
                'text' => $priceData['price'],
                'feed' => $feedPrice,
                'font' => 'bold',
                'align' => 'right',
            ];
            // draw Subtotal
            $lines[$i][] = [
                'text' => $priceData['subtotal'],
                'feed' => $feedSubtotal,
                'font' => 'bold',
                'align' => 'right',
            ];
            $i++;
        }

        // draw Tax
        $lines[0][] = [
            'text' => $order->formatPriceTxt($item->getDiscountAmount()),
            'feed' => 495,
            'font' => 'bold',
            'align' => 'right',
        ];

        // custom options
        $options = $this->getItemOptions();
        if ($options) {
            foreach ($options as $option) {
                // draw options label
                $lines[][] = [
                    'text' => $this->string->split($this->filterManager->stripTags($option['label']), 40, true, true),
                    'font' => 'italic',
                    'feed' => 35,
                ];

                if ($option['value']) {
                    if (isset($option['print_value'])) {
                        $printValue = $option['print_value'];
                    } else {
                        $printValue = $this->filterManager->stripTags($option['value']);
                    }
                    $values = explode(', ', $printValue);
                    foreach ($values as $value) {
                        $lines[][] = ['text' => $this->string->split($value, 30, true, true), 'feed' => 40];
                    }
                }
            }
        }

        $lineBlock = ['lines' => $lines, 'height' => 20];

        $page = $pdf->drawLineBlocks($page, [$lineBlock], ['table_header' => true]);
        $this->setPage($page);
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getItemPricesForDisplay()
    {
        $order = $this->getOrder();
        $item = $this->getItem();
        if ($this->_taxData->displaySalesBothPrices()) {
            $prices = [
                [
                    'label' => __('Excl. Tax') . ':',
                    'price' => $order->formatPriceTxt($item->getBasePrice() - $item->getDiscountAmount()),
                    'discountAmount' => $order->formatPriceTxt($item->getDiscountAmount()),
                    'subtotal' => $order->formatPriceTxt($item->getRowTotal()),
                ],
                [
                    'label' => __('Incl. Tax') . ':',
                    'price' => $order->formatPriceTxt($item->getPriceInclTax()),
                    'discountAmount' => $order->formatPriceTxt($item->getDiscountAmount()),
                    'subtotal' => $order->formatPriceTxt($item->getRowTotalInclTax())
                ],
            ];
        } elseif ($this->_taxData->displaySalesPriceInclTax()) {
            $prices = [
                [
                    'price' => $order->formatPriceTxt($item->getPriceInclTax()),
                    'discountAmount' => $order->formatPriceTxt($item->getDiscountAmount()),
                    'subtotal' => $order->formatPriceTxt($item->getRowTotalInclTax()),
                ],
            ];
        } else {
            $prices = [
                [
                    'price' => $order->formatPriceTxt($item->getBasePrice()),
                    'discountAmount' => $order->formatPriceTxt($item->getDiscountAmount()),
                    'subtotal' => $order->formatPriceTxt($item->getRowTotal() - $item->getDiscountAmount()),
                ],
            ];
        }
        return $prices;
    }
}