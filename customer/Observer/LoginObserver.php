<?php
namespace Ls\Customer\Observer;

use Magento\Framework\Event\ObserverInterface;
use Zend_Validate;
use Zend_Validate_EmailAddress;
use Ls\Omni\Helper\ContactHelper;
use Ls\Omni\Client\Ecommerce\Entity;

class LoginObserver implements ObserverInterface
{
    private $contactHelper;
    public function __construct(ContactHelper $contactHelper)
    {
        //Observer initialization code...
        //You can use dependency injection to get any class this observer may need.
        $this->contactHelper = $contactHelper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        //Observer execution code...
        #return;
        #if ( LSR::isLSR() ) {
            /** @var Mage_Customer_AccountController $controller_action */
            $controller_action = $observer->getData( 'controller_action' );

            $login = $controller_action->getRequest()->getPost( 'login' );
            $email = $username = $login[ 'username' ];

            $is_email = Zend_Validate::is( $username, Zend_Validate_EmailAddress::class );

            // CASE FOR EMAIL LOGIN := TRANSLATION TO USERNAME
            if ( $is_email ) {
                // we trigger an api call only if the supplied username is an email
                $search = $this->contactHelper->search( $username );
                $found = !is_null( $search )
                    && ( $search instanceof Entity\ContactPOS )
                    && !empty( $search->getEmail() );

                if ( !$found ) {
                    // TODO: fix message
                    $customer_session->addError( 'There are no members with that email' );

                    return $this;
                }
                $email = $search->getEmail();
            }


            if ( $is_email ) {
                $customer = Mage::getModel( 'customer/customer' )
                    ->getCollection()
                    ->addAttributeToSelect( 'lsr_username' )
                    ->addFieldToFilter( 'email', array( 'eq' => $email ) )
                    ->getFirstItem();
                if ( empty( $customer->getId() ) ) {
                    $customer_session->addError( 'Unfortunately email login is only available for members registered in Magento' );

                    return $this;
                } else {
                    // WE USE USERNAMES TO LOGIN IN OMNI
                    $username = $customer->getData( 'lsr_username' );
                }
            }

            // TRY TO LOGIN
            $result = Mage::helper( 'lsr_omni/omni_contact' )
                ->login( $username, $login[ 'password' ] );

            if ( $result == FALSE ) {
                //$customer_session->addError( 'Invalid Omni login or Omni password' );
                LSR::getLogger(LSR::LOG_OMNICLIENT)->error('Invalid Omni login or Omni password');

                return $this;
            }

            if ( $result instanceof LSR_Omni_Model_Omni_Domain_Contact ) {

                $customer = Mage::getModel( 'customer/customer' )
                    ->getCollection()
                    ->addFieldToFilter( 'email', array( 'eq' => $result->getEmail() ) )
                    ->getFirstItem();
                if ( empty( $customer->getId() ) ) {
                    $customer = Mage::helper( 'lsr_omni/omni_contact' )->customer( $result , $login[ 'password' ] );
                }
                if ( is_null( $customer->getData( 'lsr_id' ) ) ) {
                    $customer->setData( 'lsr_id', $result->getId() );
                }
                if ( !$is_email && empty( $customer->getData( 'lsr_username' ) ) ) {
                    $customer->setData( 'lsr_username', $username );
                }
                $token = $result->getDevice()
                    ->getSecurityToken();

                // save the OneList as the cart
                // TODO: merge with maybe existing cart

                // load OneLists from $result
                /** @var LSR_Omni_Model_Omni_Domain_ArrayOf_OneList $oneListArray */
                $oneListArray = $result->getOneList()->getOneList();
                // filter for basket OneLists
                $basketOneLists = array_filter($oneListArray, function($oneList) { return $oneList->getListType() == 'Basket';});
                if (count($basketOneLists) > 1) {
                    LSR::getLogger(LSR::LOG_GENERAL)->error("Multiple OneLists with type basket for customer.");
                } else {
                    // TODO: OMNI-3410 Synchronize OneList with Apps
                }

                // THIS IS FOR LATER IF THE CONFIG DATA FOR ORDER CREATION IS NOT INLINE
                $customer->setData( 'lsr_token', $token );
                $customer->save();

                Mage::register( LSR::REGISTRY_LOYALTY_LOGINRESULT, $result );
                $customer_session->setData( LSR::SESSION_CUSTOMER_SECURITYTOKEN, $token );
                $customer_session->setData( LSR::SESSION_CUSTOMER_LSRID, $result->getId() );

                /** @var LSR_Omni_Model_Omni_Domain_Card $card */
                $card = $result->getCard();
                if ( $card instanceof LSR_Omni_Model_Omni_Domain_Card && !is_null( $card->getId() ) ) {
                    $customer_session->setData( LSR::SESSION_CUSTOMER_CARDID, $card->getId() );
                }

                $customer_session->setCustomerAsLoggedIn( $customer );
            } else {
                $customer_session->addError( 'The service is currently unavailable. Please try again later.' );
            }
        #}

        return $this;

    }
}
