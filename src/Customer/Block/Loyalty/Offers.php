<?php
namespace Ls\Customer\Block\Loyalty;


use Ls\Omni\Helper\LoyaltyHelper;
use Magento\Store\Model\StoreManagerInterface;

class Offers extends \Magento\Framework\View\Element\Template
{

    /* @var \Ls\Omni\Helper\LoyaltyHelper */
    private $loyaltyHelper;

    /* @var \Magento\Framework\Filesystem\Io\File $_file */
    protected $_file;

    /* @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    /**
     * Offers constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param LoyaltyHelper $loyaltyHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Filesystem\Io\File $file
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        LoyaltyHelper $loyaltyHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Filesystem\Io\File $file,
        StoreManagerInterface $storeManager,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->loyaltyHelper = $loyaltyHelper;
        $this->logger = $logger;
        $this->_file = $file;
        $this->storeManager = $storeManager;
    }


    /**
     * @return \Ls\Omni\Client\Ecommerce\Entity\ArrayOfPublishedOffer |false
     */
    public function getOffers()
    {

        /* \Ls\Omni\Client\Ecommerce\Entity\ArrayOfPublishedOffer $result */
        $result = $this->loyaltyHelper->getOffers()
            ->getPublishedOffer();
        return $result;
    }

    /**
     * @param \Ls\Omni\Client\Ecommerce\Entity\PublishedOffer $coupon
     * @return array|\Ls\Omni\Client\Ecommerce\Entity\ImageView|\Ls\Omni\Client\Ecommerce\Entity\ImageView[]|mixed
     */
    public function fetchImages(\Ls\Omni\Client\Ecommerce\Entity\PublishedOffer $coupon)
    {

        $images = array();
        $index = 0;

        $img = $coupon->getImages()
            ->getImageView();


        if (empty($img)) {
            return $img;
        }
        // Normally it should return a single object, but in case if it return multiple images than we are only considering the first one,
        if (is_array($img)) {
            $img = $img[0];
        }
        $index++;
        $img_size = $img->getImgSize();
        if ($img_size->getWidth() == 0 || $img_size->getHeight() == 0) {
            $img_size->setWidth(100);
            $img_size->setHeight(100);
        }

        $result = $this->loyaltyHelper->getImageById($img->getId(), $img_size);

        if ($result instanceof \Ls\Omni\Client\Ecommerce\Entity\ImageView) {
            $offerpath = $this->getMediaPathtoStore();
            if (!is_dir($offerpath)) {
                $this->_file->mkdir($offerpath, 0775);
            }
            $format = strtolower($result->getFormat());
            $id = $img->getId();
            $output_file = "{$id}-{$index}.$format";
            $file = "{$offerpath}{$output_file}";

            if (!$this->_file->fileExists($file)) {
                $base64 = $result->getImage();
                $image_file = fopen($file, 'wb');
                fwrite($image_file, base64_decode($base64));
                fclose($image_file);
            }
            $images[] = "{$output_file}";

        }

        return $images;
    }

    /**
     * @return string
     */
    protected function getMediaPathtoStore()
    {
        return $this->getMediaDirectory()->getAbsolutePath(). "ls" . DIRECTORY_SEPARATOR . "offers" . DIRECTORY_SEPARATOR;
    }

    /**
     * @return string
     */
    public function getMediaPathToLoad()
    {
        return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . DIRECTORY_SEPARATOR . "ls" . DIRECTORY_SEPARATOR . "offers" . DIRECTORY_SEPARATOR;
    }

}