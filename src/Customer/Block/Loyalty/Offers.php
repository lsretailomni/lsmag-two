<?php

namespace Ls\Customer\Block\Loyalty;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\LineType;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\OfferDiscountLineType;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\OfferDiscountType;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\OfferType;
use \Ls\Omni\Client\Ecommerce\Entity\ImageView;
use \Ls\Omni\Client\Ecommerce\Entity\PublishedOffer;
use \Ls\Omni\Helper\LoyaltyHelper;
use \Ls\Replication\Helper\ReplicationHelper;
use Magento\Catalog\Helper\Category as CategoryHelper;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;

class Offers extends Template
{
    /**
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
     * @param ReplicationHelper $replicationHelper
     * @param array $data
     */
    public function __construct(
        public Context $context,
        public LoyaltyHelper $loyaltyHelper,
        public File $file,
        public StoreManagerInterface $storeManager,
        public ScopeConfigInterface $scopeConfig,
        public TimezoneInterface $timeZoneInterface,
        public ProductRepository $productRepository,
        public CategoryRepository $categoryRepository,
        public CategoryHelper $categoryHelper,
        public LSR $lsr,
        public ReplicationHelper $replicationHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Fetch Offers
     *
     * @return PublishedOffer[]
     */
    public function getOffers()
    {
        $offersArr = [];
        $result = $this->loyaltyHelper->getOffers();
        
        $publishedOffers = $result->getPublishedOffer();
        foreach ($publishedOffers as $pubOffer) {
            $offersArr[$pubOffer->getNo()]['Offer']  = $pubOffer;
        }

        $publishedOffersImages = $result->getPublishedOfferImages();
        foreach ($publishedOffersImages as $pubOfferImage) {
            $offerKey = $pubOfferImage->getKeyValue();
            if (array_key_exists($offerKey, $offersArr)) {
                $offersArr[$offerKey]['ImageId'] = $pubOfferImage;
            }
        }

        $publishedOffersLines = $result->getPublishedOfferLine();
        foreach ($publishedOffersLines as $pubOfferLine) {
            $offerKey = $pubOfferLine->getPublishedOfferNo();
            if (array_key_exists($offerKey, $offersArr)) {
                $offersArr[$offerKey]['OfferLines'][] = $pubOfferLine;
            }
        }
        
        return $offersArr;
    }

    /**
     * Fetch Images
     *
     * @param $coupon
     * @return array|ImageView|ImageView[]|mixed
     */
    public function fetchImages($coupon)
    {
        try {
            $images = [];
            $index  = 0;
            //$img    = $coupon->getImages()->getImageView();
            $img    = $coupon['ImageId'];
            if (empty($img)) {
                return $img;
            }
            // Normally it should return a single object, but in case if it
            // return multiple images than we are only considering the first one,
            if (is_array($img)) {
                $img = $img[0];
            }
            $index++;
//            $img_size = $img->getImgSize();
//            if ($img_size->getWidth() == 0 || $img_size->getHeight() == 0) {
//                $imageSize = $this->getImageWidthandHeight();
//                $img_size->setWidth($imageSize[0]);
//                $img_size->setHeight($imageSize[1]);
//            }

//            $result = $this->loyaltyHelper->getImageById($img->getId(), $img_size);

//            if (!empty($result) && !empty($result['format']) && !empty($result['image'])) {
//                $offerpath = $this->getMediaPathtoStore();
//                // @codingStandardsIgnoreStart
//                if (!is_dir($offerpath)) {
//                    $this->file->mkdir($offerpath, 0775);
//                }
//                $format      = strtolower($result['format']);
//                $id          = $img->getId();
//                $output_file = "{$id}-{$index}.$format";
//                $file        = "{$offerpath}{$output_file}";
//
//                if (!$this->file->fileExists($file)) {
//                    $base64     = $result['image'];
//                    $image_file = fopen($file, 'wb');
//                    fwrite($image_file, base64_decode($base64));
//                    fclose($image_file);
//                }
//                // @codingStandardsIgnoreEnd
//                $images[] = "{$output_file}";
//            }
            return $images;
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
    }

    /**
     * Get media path to store
     *
     * @return string
     * @throws ValidatorException
     */
    public function getMediaPathtoStore()
    {
        return $this->getMediaDirectory()
                ->getAbsolutePath() . 'ls' . DIRECTORY_SEPARATOR . 'offers' . DIRECTORY_SEPARATOR;
    }

    /**
     * Get media path to load
     *
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
     * Get image width and height
     *
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
     * Get formated offer expiry date
     *
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
     * Get offer product category links
     * 
     * @param $offer
     * @return array|string[]|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getOfferProductCategoryLink($offer)
    {
        $url  = '';
        $text = '';
        $offerLines = $offer['OfferLines'];
        if (count($offerLines) == 1) {
            try {
                $lineType = $this->getDiscountLineType($offerLines[0]->getDiscountLineType());
                
                if ($lineType == OfferDiscountLineType::ITEM) {
                    $product = $this->replicationHelper->getProductDataByIdentificationAttributes(
                        $offerLines[0]->getDiscountLineId()
                    );
                    $url     = $product->getProductUrl();
                    $text    = __("Go To Product");
                }
                if ($lineType == OfferDiscountLineType::PRODUCT_GROUP
                    || $lineType == OfferDiscountLineType::ITEM_CATEGORY
                    || $lineType == OfferDiscountLineType::SPECIAL_GROUP
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
                $lineType = $this->getDiscountLineType($offerLine->getDiscountLineType());
                if ($lineType == LineType::ITEM) {
                    try {
                        $catIds = $this->replicationHelper->getProductDataByIdentificationAttributes(
                            $offerLine->getDiscountLineId()
                        )->getCategoryIds();
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
                } elseif ($lineType == OfferDiscountLineType::PRODUCT_GROUP
                    || $lineType == OfferDiscountLineType::ITEM_CATEGORY
                    || $lineType == OfferDiscountLineType::SPECIAL_GROUP
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
                    $product = $this->replicationHelper->getProductDataByIdentificationAttributes(
                        $offerLines[0]->getDiscountLineId()
                    );
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

    /**
     * Get discount line type
     *
     * @param $lineType
     * @return string|void
     */
    public function getDiscountLineType($lineType)
    {
        switch ($lineType) {
            case 0:
                return OfferDiscountLineType::ITEM;
            case 1:
                return OfferDiscountLineType::PRODUCT_GROUP;
            case 2:
                return OfferDiscountLineType::ITEM_CATEGORY;
            case 3:
                return OfferDiscountLineType::ALL;
            case 4:
                return OfferDiscountLineType::P_L_U_MENU;
            case 5:
                return OfferDiscountLineType::DEAL_MODIFIER;
            case 6:
                return OfferDiscountLineType::SPECIAL_GROUP;
        }
    }

    /**
     * Get discount type
     *
     * @param $discType
     * @return string|void
     */
    public function getDiscountType($discType)
    {
        switch ($discType) {
            case 0:
                return OfferDiscountType::PROMOTION;
            case 1:
                return OfferDiscountType::DEAL;
            case 2:
                return OfferDiscountType::MULTIBUY;
            case 3:
                return OfferDiscountType::MIX_AND_MATCH;
            case 4:
                return OfferDiscountType::DISCOUNT_OFFER;
            case 5:
                return OfferDiscountType::TOTAL_DISCOUNT;
            case 6:
                return OfferDiscountType::TENDER_TYPE;
            case 7:
                return OfferDiscountType::ITEM_POINT;
            case 8:
                return OfferDiscountType::LINE_DISCOUNT;
            case 9:
                return OfferDiscountType::COUPON;
        }
    }

    /**
     * Get Offer type
     *
     * @param $type
     * @return string|void
     */
    public function getOfferType($type)
    {
        switch ($type) {
            case 0:
                // not a part of member mgt system
                return OfferType::GENERAL;
            case 1:
                //member attribute based, specifically the member contact signed up for this (golf etc)
                return OfferType::SPECIAL_MEMBER;
            case 2:
                //Points and Coupons, item Points and Coupons,  something you can pay with
                return OfferType::POINT_OFFER;
            case 3:
                //Club and scheme, offer is part of member mgt,
                //got offer since you were in club,scheme,account or contact
                return OfferType::CLUB;
            
        }
    }
}
