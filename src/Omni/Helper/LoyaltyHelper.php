<?php

namespace Ls\Omni\Helper;


use Ls\Omni\Client\Ecommerce\Entity;
use Ls\Omni\Client\Ecommerce\Operation;
use Ls\Core\Model\LSR;
use Magento\Framework\App\Filesystem\DirectoryList;

class LoyaltyHelper extends \Magento\Framework\App\Helper\AbstractHelper
{

    const SERVICE_TYPE = 'ecommerce';

    /** @var \Magento\Framework\Api\FilterBuilder */
    protected $filterBuilder;

    /** @var \Magento\Framework\Api\SearchCriteriaBuilder */
    protected $searchCriteriaBuilder;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    /** @var \Magento\Customer\Api\CustomerRepositoryInterface */
    protected $customerRepository;

    /** @var \Magento\Customer\Model\CustomerFactory */
    protected $customerFactory;

    /** @var \Magento\Customer\Model\Session */
    protected $customerSession;

    /** @var null */
    protected $_ns = NULL;

    /** @var \Magento\Framework\Filesystem */
    protected $_filesystem;

    /**
     * LoyaltyHelper constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Filesystem $Filesystem
     */

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Filesystem $Filesystem
    )
    {
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->storeManager = $storeManager;
        $this->customerRepository = $customerRepository;
        $this->customerFactory = $customerFactory;
        $this->customerSession = $customerSession;
        $this->_filesystem = $Filesystem;

        parent::__construct(
            $context
        );
    }

    /**
     * @return Entity\ArrayOfProfile|Entity\ProfilesGetAllResponse|\Ls\Omni\Client\ResponseInterface|null
     */
    public function getAllProfiles()
    {
        $response = NULL;
        $request = new Operation\ProfilesGetAll();
        $entity = new Entity\ProfilesGetAll();
        try {
            $response = $request->execute($entity);
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getResult() : $response;
    }

    /**
     * @return Entity\ArrayOfPublishedOffer|Entity\PublishedOffersGetByCardIdResponse|\Ls\Omni\Client\ResponseInterface|null
     */
    public function getOffers()
    {


        $response = NULL;
        $customer = $this->customerSession->getCustomer();
        $request = new Operation\PublishedOffersGetByCardId();
        $request->setToken($customer->getData('lsr_token'));

        $entity = new Entity\PublishedOffersGetByCardId();
        $entity->setCardId($customer->getData('lsr_cardid'));


        try {
            $response = $request->execute($entity);
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getResult() : $response;
    }

    /**
     * @param null $image_id
     * @param null $image_size
     * @return Entity\ImageGetByIdResponse|Entity\ImageView|\Ls\Omni\Client\ResponseInterface|null
     */
    public function getImageById($image_id = NULL, $image_size = NULL)
    {

        $response = NULL;
        if ($image_id == NULL || $image_size == NULL) {
            return $response;
        }

        $request = new Operation\ImageGetById();
        $entity = new Entity\ImageGetById();
        $entity->setId($image_id)
            ->setImageSize($image_size);


        try {
            $response = $request->execute($entity);
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getResult() : $response;
    }

    public function convertPointsIntoValues()
    {

        $points = $pointrate = $value = 0;

        /* \Ls\Omni\Client\Ecommerce\Entity\MemberContact $memberProfile */
        $memberProfile = $this->getMemberInfo();
        $pointrate = $this->getPointRate();

        // check if we have something in there.
        if (!is_null($memberProfile) and !is_null($pointrate)) {
            $points = $memberProfile->getAccount()->getPointBalance();
            $value = $points * $pointrate;
            return $value;
        } else {
            // if no then just return 0 value
            return 0;
        }

    }

    /**
     * @return Entity\ContactGetByIdResponse|Entity\MemberContact|\Ls\Omni\Client\ResponseInterface|null
     */
    public function getMemberInfo()
    {

        $response = NULL;
        $customer = $this->customerSession->getCustomer();
        $lsrId = $this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID);
        // if not set in seesion then get it from customer database.
        if (!$lsrId) {
            $lsrId = $customer->getData('lsr_id');
        }

        $request = new Operation\ContactGetById();
        $request->setToken($customer->getData('lsr_token'));
        $entity = new Entity\ContactGetById();
        $entity->setContactId($lsrId);

        try {
            $response = $request->execute($entity);
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getResult() : $response;
    }

    /*
     * Convert Point Rate into Values
     */

    /**
     * @return float|Entity\GetPointRateResponse|\Ls\Omni\Client\ResponseInterface|null
     */
    public function getPointRate()
    {
        $response = NULL;
        $request = new Operation\GetPointRate();
        $entity = new Entity\GetPointRate();
        try {
            $response = $request->execute($entity);
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getResult() : $response;

    }

    /**
     * @param null $size
     * @return Entity\ImageSize
     */
    public function getImageSize($size = NULL)
    {

        $imagesize = new Entity\ImageSize();
        $imagesize->setHeight($size['height'])
            ->setWidth($size['width']);
        return $imagesize;


    }

    /**
     * @return string
     */
    public function getMediaPathtoStore()
    {
        return $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();

    }

}
