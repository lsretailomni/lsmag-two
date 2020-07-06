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
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\Helper\Data;
use Psr\Log\LoggerInterface;
use Zend_Validate;
use Zend_Validate_EmailAddress;
use Zend_Validate_Exception;

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

    /** @var CustomerFactory */
    private $customerFactory;

    /** @var JsonFactory */
    private $resultJsonFactory;

    /** @var LSR */
    private $lsr;

    /**
     * AjaxLoginObserver constructor.
     * @param ContactHelper $contactHelper
     * @param LoggerInterface $logger
     * @param Proxy $customerSession
     * @param Data $jsonhelper
     * @param JsonFactory $resultJsonFactory
     * @param ActionFlag $actionFlag
     * @param CustomerFactory $customerFactory
     * @param LSR $LSR
     */
    public function __construct(
        ContactHelper $contactHelper,
        LoggerInterface $logger,
        Proxy $customerSession,
        Data $jsonhelper,
        JsonFactory $resultJsonFactory,
        ActionFlag $actionFlag,
        CustomerFactory $customerFactory,
        LSR $LSR
    ) {
        $this->contactHelper     = $contactHelper;
        $this->logger            = $logger;
        $this->customerSession   = $customerSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->jsonhelper        = $jsonhelper;
        $this->actionFlag        = $actionFlag;
        $this->customerFactory   = $customerFactory;
        $this->lsr               = $LSR;
    }

    /**
     * @param Observer $observer
     * @return $this|void
     * @throws LocalizedException
     * @throws Zend_Validate_Exception
     */
    public function execute(Observer $observer)
    {
        /** @var $request RequestInterface */
        $request = $observer->getEvent()->getRequest();
        $resultJson = $this->resultJsonFactory->create();
        // check if we have a data in request and request is Ajax.
        if ($request && $request->isXmlHttpRequest()) {
            $credentials = $this->jsonhelper->jsonDecode($request->getContent());

            if (!empty($credentials['username']) && !empty($credentials['password'])) {
                $email     = $username = $credentials['username'];
                $is_email  = Zend_Validate::is($username, Zend_Validate_EmailAddress::class);
                if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
                    try {
                        // CASE FOR EMAIL LOGIN := TRANSLATION TO USERNAME
                        if ($is_email) {
                            $search = $this->contactHelper->search($username);
                            $found  = $search !== null
                                && ($search instanceof Entity\MemberContact)
                                && !empty($search->getEmail());
                            if (!$found) {
                                $message = __('Sorry. No account found with the provided email address');
                                return $this->generateMessage($observer, $message, true);
                            }
                            $username = $search->getUserName();
                        }
                        $result = $this->contactHelper->login($username, $credentials['password']);
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
                            $oneListBasket = $this->contactHelper->getOneListTypeObject(
                                $result->getOneLists()->getOneList(),
                                Entity\Enum\ListType::BASKET
                            );
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
                    } catch (Exception $e) {
                        $this->logger->error($e->getMessage());
                    }
                } else {
                    $isAjax = true;
                    $this->contactHelper->loginCustomerIfOmniServiceDown($is_email, $email, $request, $isAjax);
                }
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
