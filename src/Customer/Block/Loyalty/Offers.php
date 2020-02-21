<?php

namespace Ls\Customer\Block\Loyalty;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\LineType;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\OfferDiscountLineType;
use \Ls\Omni\Client\Ecommerce\Entity\ImageView;
use \Ls\Omni\Client\Ecommerce\Entity\PublishedOffer;
use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Catalog\Helper\Category as CategoryHelper;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Offers
 * @package Ls\Customer\Block\Loyalty
 */
class Offers extends Template
{

    /**
     * @var LoyaltyHelper
     */
    private $loyaltyHelper;

    /**
     * @var File
     */
    public $file;

    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @var TimezoneInterface
     */
    public $timeZoneInterface;

    /** @var ProductRepository */
    public $productRepository;

    /** @var CategoryRepository */
    public $categoryRepository;

    /** @var CategoryHelper */
    public $categoryHelper;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * Offers constructor.
     * @param Context $context
     * @param LoyaltyHelper $loyaltyHelper
     * @param File $file
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param TimezoneInterface $timeZoneInterface
     * @param ProductRepository $productRepository
     * @param CategoryRepository $categoryRepository
     * @param CategoryHelper $categoryHelper
     * @param LSR $lsr
     * @param array $data
     */
    public function __construct(
        Context $context,
        LoyaltyHelper $loyaltyHelper,
        File $file,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        TimezoneInterface $timeZoneInterface,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        CategoryHelper $categoryHelper,
        LSR $lsr,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->loyaltyHelper      = $loyaltyHelper;
        $this->file               = $file;
        $this->storeManager       = $storeManager;
        $this->scopeConfig        = $scopeConfig;
        $this->timeZoneInterface  = $timeZoneInterface;
        $this->productRepository  = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->categoryHelper     = $categoryHelper;
        $this->lsr                = $lsr;
    }

    /**
     * @return PublishedOffer[]
     */
    public function getOffers()
    {
        $result = $this->loyaltyHelper->getOffers();
        return ($result) ? $result->getPublishedOffer() : '';
    }

    /**
     * @param PublishedOffer $coupon
     * @return array|ImageView|ImageView[]|mixed
     */
    public function fetchImages(PublishedOffer $coupon)
    {
        try {
            $images = [];
            $index  = 0;
            $img    = $coupon->getImages()->getImageView();
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

            if (!empty($result) && !empty($result['format']) && !empty($result['image'])) {
                $offerpath = $this->getMediaPathtoStore();
                // @codingStandardsIgnoreStart
                if (!is_dir($offerpath)) {
                    $this->file->mkdir($offerpath, 0775);
                }
                $format      = strtolower($result['format']);
                $id          = $img->getId();
                $output_file = "{$id}-{$index}.$format";
                $file        = "{$offerpath}{$output_file}";

                if (!$this->file->fileExists($file)) {
                    $base64     = $result['image'];
                    $image_file = fopen($file, 'wb');
                    fwrite($image_file, base64_decode($base64));
                    fclose($image_file);
                }
                // @codingStandardsIgnoreEnd
                $images[] = "{$output_file}";
            }
            return $images;
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
    }

    /**
     * @return string
     * @throws ValidatorException
     */
    public function getMediaPathtoStore()
    {
        return $this->getMediaDirectory()
                ->getAbsolutePath() . 'ls' . DIRECTORY_SEPARATOR . 'offers' . DIRECTORY_SEPARATOR;
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getMediaPathToLoad()
    {
        return $this->storeManager->getStore()
                ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA)
            . DIRECTORY_SEPARATOR . 'ls' . DIRECTORY_SEPARATOR . 'offers' . DIRECTORY_SEPARATOR;
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
        } catch (Exception $e) {
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
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                $this->lsr->getCurrentStoreId()
            ));
            return $offerExpiryDate;
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
    }

    /**
     * @param $offerLines
     * @return array|null
     * @throws NoSuchEntityException
     */
    public function getOfferProductCategoryLink($offerLines)
    {
        $url  = '';
        $text = '';
        if (count($offerLines) == 1) {
            try {
                if ($offerLines[0]->getLineType() == OfferDiscountLineType::ITEM) {
                    $product = $this->productRepository->get($offerLines[0]->getId());
                    $url     = $product->getProductUrl();
                    $text    = __("Go To Product");
                }
                if ($offerLines[0]->getLineType() == OfferDiscountLineType::PRODUCT_GROUP
                    || $offerLines[0]->getLineType() == OfferDiscountLineType::ITEM_CATEGORY
                    || $offerLines[0]->getLineType() == OfferDiscountLineType::SPECIAL_GROUP
                ) {
                    return ['', ''];
                }
            } catch (NoSuchEntityException $e) {
                return null;
            }
        } else {
            $categoryIds = [];
            $count       = 0;
            foreach ($offerLines as $offerLine) {
                if ($offerLine->getLineType() == LineType::ITEM) {
                    try {
                        $catIds = $this->productRepository->get($offerLine->getId())->getCategoryIds();
                    } catch (Exception $e) {
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
                } elseif ($offerLine->getLineType() == OfferDiscountLineType::PRODUCT_GROUP
                    || $offerLine->getLineType() == OfferDiscountLineType::ITEM_CATEGORY
                    || $offerLine->getLineType() == OfferDiscountLineType::SPECIAL_GROUP
                ) {
                    return ['', ''];
                }
            }
            if (!empty($categoryIds)) {
                $categoryIds = array_values($categoryIds);
                $category    = $this->categoryRepository->get($categoryIds[count($categoryIds) - 1]);
                $url         = $this->categoryHelper->getCategoryUrl($category);
                $text        = __('Go To Category');
            } else {
                try {
                    $product = $this->productRepository->get($offerLines[0]->getId());
                    $url     = $product->getProductUrl();
                    $text    = __('Go To Product');
                } catch (NoSuchEntityException $e) {
                    return ['', ''];
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
