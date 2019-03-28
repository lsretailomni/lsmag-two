<?php

namespace Ls\Customer\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Zend_Validate;
use Zend_Validate_EmailAddress;
use \Ls\Omni\Helper\ContactHelper;
use \Ls\Omni\Client\Ecommerce\Entity;

/**
 * Class AjaxLoginObserver
 * @package Ls\Customer\Observer
 */
class AjaxLoginObserver implements ObserverInterface
{

    /** @var ContactHelper */
    private $contactHelper;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var \Magento\Customer\Model\Session\Proxy */
    private $customerSession;

    /** @var \Magento\Framework\App\ActionFlag */
    private $actionFlag;

    /** @var \Magento\Framework\Json\Helper\Data $jsonhelper */
    private $jsonhelper;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    private $storeManage;

    /** @var \Magento\Customer\Model\CustomerFactory */
    private $customerFactory;

    /** @var \Magento\Framework\Controller\Result\JsonFactory */
    private $resultJsonFactory;

    /** @var \Ls\Core\Model\LSR @var  */
    private $lsr;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * AjaxLoginObserver constructor.
     * @param ContactHelper $contactHelper
     * @param \Magento\Framework\Registry $registry
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param \Magento\Framework\Json\Helper\Data $jsonhelper
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\App\ActionFlag $actionFlag
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     */
    public function __construct(
        ContactHelper $contactHelper,
        \Magento\Framework\Registry $registry,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        \Magento\Framework\Json\Helper\Data $jsonhelper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\ActionFlag $actionFlag,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Ls\Core\Model\LSR $LSR
    ) {
        $this->contactHelper = $contactHelper;
        $this->registry = $registry;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->jsonhelper = $jsonhelper;
        $this->actionFlag = $actionFlag;
        $this->storeManage = $storeManager;
        $this->customerFactory = $customerFactory;
        $this->lsr  =   $LSR;
    }

    /**
     * @param Observer $observer
     * @return $this|AjaxLoginObserver|\Magento\Framework\Controller\Result\Json
     */
    public function execute(Observer $observer)
    {
        /*
         * Adding condition to only process if LSR is enabled.
         */
        if ($this->lsr->isLSR()) {
            try {
                /** @var $request \Magento\Framework\App\RequestInterface */
                $request = $observer->getEvent()->getRequest();
                /** @var \Magento\Framework\Controller\Result\Json $resultJson */
                $resultJson = $this->resultJsonFactory->create();
                // check if we have a data in request and request is Ajax.
                if ($request && $request->isXmlHttpRequest()) {
                    $credentials = $this->jsonhelper->jsonDecode($request->getContent());
                    $email = $username = $credentials['username'];
                    $websiteId = $this->storeManage->getWebsite()->getWebsiteId();
                    $is_email = Zend_Validate::is($username, Zend_Validate_EmailAddress::class);
                    // CASE FOR EMAIL LOGIN := TRANSLATION TO USERNAME
                    if ($is_email) {
                        $search = $this->contactHelper->search($username);
                        $found = $search !== null
                            && ($search instanceof Entity\MemberContact)
                            && !empty($search->getEmail());
                        if (!$found) {
                            $message = __('Sorry. No account found with the provided email address');
                            return $this->generateMessage($observer, $message, true);
                        }
                        $email = $search->getEmail();
                    }
                    if ($is_email) {
                        $searchResults = $this->contactHelper->searchCustomerByEmail($email);
                        if ($searchResults->getTotalCount() == 0) {
                            $message = __(
                                'Unfortunately email login is only available for members registered in Magento'
                            );
                            return $this->generateMessage($observer, $message, true);
                        } else {
                            $customerObj = null;
                            foreach ($searchResults->getItems() as $match) {
                                $customerObj = $this->customerFactory->create()->setWebsiteId($websiteId)
                                    ->loadByEmail($email);
                                break;
                            }
                            $username = $customerObj->getData('lsr_username');
                        }
                    }
                    $result = $this->contactHelper->login($username, $credentials['password']);
                    if ($result == false) {
                        $message = __('Invalid Omni login or Omni password');
                        return $this->generateMessage($observer, $message, true);
                    }
                    $response = [
                        'errors' => false,
                        'message' => __('Omni login successful.')
                    ];
                    if ($result instanceof Entity\MemberContact) {
                        /**
                         * Fetch customer related info from omni and create user in magento
                         */
                        $this->contactHelper->processCustomerLogin($result, $credentials, $is_email);
                        /** Update Basket to Omni */
                        $this->contactHelper->updateBasketAfterLogin(
                            $result->getBasket(),
                            $result->getId(),
                            $result->getCard()->getId()
                        );
                        $this->customerSession->regenerateId();
                        $this->actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);
                        return $resultJson->setData($response);
                    } else {
                        $message = __('The service is currently unavailable. Please try again later.');
                        return $this->generateMessage($observer, $message, true);
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
        return $this;
    }

    /**
     * @param Observer $observer
     * @param $message
     * @param bool $isError
     * @return $this
     */
    private function generateMessage(\Magento\Framework\Event\Observer $observer, $message, $isError = true)
    {
        $response = [
            'errors' => $isError,
            'message' => __($message)
        ];
        $this->actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);
        $observer->getControllerAction()
            ->getResponse()
            ->representJson($this->jsonhelper->jsonEncode($response));
        return $this;
    }

}
