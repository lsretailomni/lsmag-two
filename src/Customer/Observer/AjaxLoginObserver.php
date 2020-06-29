<?php

namespace Ls\Customer\Observer;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Helper\ContactHelper;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session\Proxy;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Zend_Validate;
use Zend_Validate_EmailAddress;

/**
 * Class AjaxLoginObserver
 * @package Ls\Customer\Observer
 */
class AjaxLoginObserver implements ObserverInterface
{

    /** @var ContactHelper */
    private $contactHelper;

    /** @var LoggerInterface */
    private $logger;

    /** @var Proxy */
    private $customerSession;

    /** @var ActionFlag */
    private $actionFlag;

    /** @var Data $jsonhelper */
    private $jsonhelper;

    /** @var StoreManagerInterface */
    private $storeManage;

    /** @var CustomerFactory */
    private $customerFactory;

    /** @var JsonFactory */
    private $resultJsonFactory;

    /** @var LSR @var */
    private $lsr;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * AjaxLoginObserver constructor.
     * @param ContactHelper $contactHelper
     * @param Registry $registry
     * @param LoggerInterface $logger
     * @param Proxy $customerSession
     * @param Data $jsonhelper
     * @param JsonFactory $resultJsonFactory
     * @param ActionFlag $actionFlag
     * @param StoreManagerInterface $storeManager
     * @param CustomerFactory $customerFactory
     */
    public function __construct(
        ContactHelper $contactHelper,
        Registry $registry,
        LoggerInterface $logger,
        Proxy $customerSession,
        Data $jsonhelper,
        JsonFactory $resultJsonFactory,
        ActionFlag $actionFlag,
        StoreManagerInterface $storeManager,
        CustomerFactory $customerFactory,
        LSR $LSR
    ) {
        $this->contactHelper     = $contactHelper;
        $this->registry          = $registry;
        $this->logger            = $logger;
        $this->customerSession   = $customerSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->jsonhelper        = $jsonhelper;
        $this->actionFlag        = $actionFlag;
        $this->storeManage       = $storeManager;
        $this->customerFactory   = $customerFactory;
        $this->lsr               = $LSR;
    }

    /**
     * @param Observer $observer
     * @return $this|AjaxLoginObserver|Json
     */
    public function execute(Observer $observer)
    {
        /*
         * Adding condition to only process if LSR is enabled.
         */
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            try {
                /** @var $request RequestInterface */
                $request = $observer->getEvent()->getRequest();
                /** @var Json $resultJson */
                $resultJson = $this->resultJsonFactory->create();
                // check if we have a data in request and request is Ajax.
                if ($request && $request->isXmlHttpRequest()) {
                    $credentials = $this->jsonhelper->jsonDecode($request->getContent());
                    $email       = $username = $credentials['username'];
                    $websiteId   = $this->storeManage->getWebsite()->getWebsiteId();
                    $is_email    = Zend_Validate::is($username, Zend_Validate_EmailAddress::class);
                    // CASE FOR EMAIL LOGIN := TRANSLATION TO USERNAME
                    if ($is_email) {
                        $search = $this->contactHelper->search($username);
                        if ($this->lsr->checkOmniService($search)) {
                            $found = $search !== null
                                && ($search instanceof Entity\MemberContact)
                                && !empty($search->getEmail());
                            if (!$found) {
                                $message = __('Sorry. No account found with the provided email address');
                                return $this->generateMessage($observer, $message, true);
                            }
                            $email = $search->getEmail();
                        }
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
                    if ($this->lsr->checkOmniService($result)) {
                        if ($result == false) {
                            $message = __('Invalid Omni login or Omni password');
                            return $this->generateMessage($observer, $message, true);
                        }
                        $response = [
                            'errors'  => false,
                            'message' => __('Omni login successful.')
                        ];
                        if ($result instanceof Entity\MemberContact) {
                            /**
                             * Fetch customer related info from omni and create user in magento
                             */
                            $this->contactHelper->processCustomerLogin($result, $credentials, $is_email);
                            $oneListBasket = $this->contactHelper->getOneListTypeObject($result->getOneLists()->getOneList(),
                                Entity\Enum\ListType::BASKET);
                            if ($oneListBasket) {
                                /** Update Basket to Omni */
                                $this->contactHelper->updateBasketAfterLogin(
                                    $oneListBasket,
                                    $result->getId(),
                                    $result->getCards()->getCard()[0]->getId()
                                );
                            }
                            $this->customerSession->regenerateId();
                            $this->actionFlag->set('', Action::FLAG_NO_DISPATCH, true);
                            return $resultJson->setData($response);
                        } else {
                            $message = __('The service is currently unavailable. Please try again later.');
                            return $this->generateMessage($observer, $message, true);
                        }
                    } else {
                        $this->contactHelper->loginCustomerIfOmniServiceDown($is_email, $email);
                    }
                }
            } catch (Exception $e) {
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
    private function generateMessage(Observer $observer, $message, $isError = true)
    {
        $response = [
            'errors'  => $isError,
            'message' => __($message)
        ];
        $this->actionFlag->set('', Action::FLAG_NO_DISPATCH, true);
        $observer->getControllerAction()
            ->getResponse()
            ->representJson($this->jsonhelper->jsonEncode($response));
        return $this;
    }

}
