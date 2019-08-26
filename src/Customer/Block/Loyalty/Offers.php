<?php

namespace Ls\Customer\Block\Loyalty;

use Ls\Core\Model\LSR;
use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\CategoryRepository;
use \Magento\Catalog\Helper\Category as CategoryHelper;

/**
 * Class Offers
 * @package Ls\Customer\Block\Loyalty
 */
class Offers extends \Magento\Framework\View\Element\Template
{

    /**
     * @var LoyaltyHelper
     */
    private $loyaltyHelper;

    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    public $file;

    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @var Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    public $timeZoneInterface;

    /** @var ProductRepositoryInterface */
    public $productRepository;

    /** @var CategoryRepositoryInterface */
    public $categoryRepository;

    /** @var CategoryHelper */
    public $categoryHelper;

    /**
     * Offers constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param LoyaltyHelper $loyaltyHelper
     * @param \Magento\Framework\Filesystem\Io\File $file
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param TimezoneInterface $timeZoneInterface
     * @param ProductRepository $productRepository
     * @param CategoryRepository $categoryRepository
     * @param CategoryHelper $categoryHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        LoyaltyHelper $loyaltyHelper,
        \Magento\Framework\Filesystem\Io\File $file,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        TimezoneInterface $timeZoneInterface,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        CategoryHelper $categoryHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->loyaltyHelper = $loyaltyHelper;
        $this->file = $file;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->timeZoneInterface = $timeZoneInterface;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->categoryHelper = $categoryHelper;
    }

    /**
     * @return \Ls\Omni\Client\Ecommerce\Entity\PublishedOffer[]
     */
    public function getOffers()
    {
        $result = $this->loyaltyHelper->getOffers();
        return ($result) ? $result->getPublishedOffer() : '';
    }

    /**
     * @param \Ls\Omni\Client\Ecommerce\Entity\PublishedOffer $coupon
     * @return array|\Ls\Omni\Client\Ecommerce\Entity\ImageView|\Ls\Omni\Client\Ecommerce\Entity\ImageView[]|mixed
     */
    public function fetchImages(\Ls\Omni\Client\Ecommerce\Entity\PublishedOffer $coupon)
    {
        try {
            $images = [];
            $index = 0;

            $img = $coupon->getImages()
                ->getImageView();

            if (empty($img)) {
                return $img;
            }
            // Normally it should return a single object, but in case if it
            // return multiple images than we are only considering the first one,
            if (is_array($img)) {
                $img = $img[0];
            }
            $index++;
            $img_size = $img->getImgSize();
            if ($img_size->getWidth() == 0 || $img_size->getHeight() == 0) {
                $imageSize = $this->getImageWidthandHeight();
                $img_size->setWidth($imageSize[0]);
                $img_size->setHeight($imageSize[1]);
            }

            $result = $this->loyaltyHelper->getImageById($img->getId(), $img_size);

            if ($result) {
                $offerpath = $this->getMediaPathtoStore();
                // @codingStandardsIgnoreStart
                if (!is_dir($offerpath)) {
                    $this->file->mkdir($offerpath, 0775);
                }
                $format = strtolower($result["format"]);
                $id = $img->getId();
                $output_file = "{$id}-{$index}.$format";
                $file = "{$offerpath}{$output_file}";

                if (!$this->file->fileExists($file)) {
                    $base64 = $result["image"];
                    $image_file = fopen($file, 'wb');
                    fwrite($image_file, base64_decode($base64));
                    fclose($image_file);
                }

                // @codingStandardsIgnoreEnd
                $images[] = "{$output_file}";
            }

            return $images;
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    public function getMediaPathtoStore()
    {
        return $this->getMediaDirectory()
                ->getAbsolutePath() . "ls" . DIRECTORY_SEPARATOR . "offers" . DIRECTORY_SEPARATOR;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getMediaPathToLoad()
    {
        return $this->storeManager->getStore()
                ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)
            . DIRECTORY_SEPARATOR . "ls" . DIRECTORY_SEPARATOR . "offers" . DIRECTORY_SEPARATOR;
    }

    /**
     * @return array
     */
    public function getImageWidthandHeight()
    {
        $size = [];
        try {
            $size[] = $this->scopeConfig->getValue(
                LSR::SC_LOYALTY_PAGE_IMAGE_WIDTH,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            );
            $size[] = $this->scopeConfig->getValue(
                LSR::SC_LOYALTY_PAGE_IMAGE_HEIGHT,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            );

            return $size;
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
    }

    /**
     * @param $date
     * @return string
     */
    public function getOfferExpiryDate($date)
    {
        try {
            $offerExpiryDate = $this->timeZoneInterface->date($date)->format($this->scopeConfig->getValue(
                LSR::SC_LOYALTY_EXPIRY_DATE_FORMAT,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            ));

            return $offerExpiryDate;
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
    }

    /**
     * @param $offerLines
     * @return array|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    // @codingStandardsIgnoreLine
    public function getOfferProductCategoryLink($offerLines)
    {
        $url = '';
        $text = '';
        if (count($offerLines) == 1) {
            try {
                if ($offerLines[0]->getLineType() == "Item") {
                    $product = $this->productRepository->get($offerLines[0]->getId());
                    $url = $product->getProductUrl();
                    $text = __("Go To Product");
                }
                if ($offerLines[0]->getLineType() == "ProductGroup") {
                    return ["", ""];
                }
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                return null;
            }
        } else {
            $categoryIds = [];
            $count = 0;
            foreach ($offerLines as $offerLine) {
                if ($offerLine->getLineType() == "Item") {
                    try {
                        $catIds = $this->productRepository->get($offerLine->getId())->getCategoryIds();
                    } catch (\Exception $e) {
                        return null;
                    }
                    if (!empty($catIds)) {
                        if ($count == 0) {
                            $categoryIds = $catIds;
                        } else {
                            $categoryIds = array_intersect($catIds, $categoryIds);
                        }
                        $count++;
                    }
                }
            }
            if (!empty($categoryIds)) {
                $categoryIds = array_values($categoryIds);
                $category = $this->categoryRepository->get($categoryIds[count($categoryIds) - 1]);
                $url = $this->categoryHelper->getCategoryUrl($category);
                $text = __("Go To Category");
            } else {
                try {
                    $product = $this->productRepository->get($offerLines[0]->getId());
                    $url = $product->getProductUrl();
                    $text = __("Go To Product");
                } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                    return ["", ""];
                }
            }
        }
        if ($url != "" && $text != "") {
            return [$url, $text];
        } else {
            return null;
        }
    }
}
