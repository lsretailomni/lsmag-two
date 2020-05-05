<?php

namespace Ls\Omni\Block\Stores;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\StoreHourOpeningType;
use \Ls\Omni\Helper\Data;
use \Ls\Replication\Model\ResourceModel\ReplStore\Collection;
use \Ls\Replication\Model\ResourceModel\ReplStore\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;

/**
 * Class Stores
 * @package Ls\Omni\Block\Stores
 */
class Stores extends Template
{
    /**
     * @var PageFactory
     */
    public $resultPageFactory;
    /**
     * @var CollectionFactory
     */
    public $replStoreFactory;
    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;
    /**
     * @var SessionManagerInterface
     */
    public $session;
    /**
     * @var Data
     */
    public $storeHoursHelper;
    /**
     * @var Data
     */
    public $logger;

    /**
     * Stores constructor.
     * @param Template\Context $context
     * @param PageFactory $resultPageFactory
     * @param CollectionFactory $replStoreCollectionFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param SessionManagerInterface $session
     * @param Data $storeHoursHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        Template\Context $context,
        PageFactory $resultPageFactory,
        CollectionFactory $replStoreCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        SessionManagerInterface $session,
        Data $storeHoursHelper,
        LoggerInterface $logger
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->replStoreFactory  = $replStoreCollectionFactory;
        $this->scopeConfig       = $scopeConfig;
        $this->session           = $session;
        $this->storeHoursHelper  = $storeHoursHelper;
        $this->logger            = $logger;
        parent::__construct($context);
    }

    /**
     * @return Collection
     */
    public function getStores()
    {
        try {
            $collection = $this->replStoreFactory->create()->addFieldToFilter('IsDeleted', 0);
            return $collection;
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * @param $storeId
     * @return array
     */
    public function getStoreHours($storeId)
    {
        try {
            $storeHours = $this->storeHoursHelper->getStoreHours($storeId);
            return $storeHours;
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * @return mixed
     */
    public function getStoreMapKey()
    {
        try {
            return $this->scopeConfig->getValue(
                LSR::SC_CLICKCOLLECT_GOOGLE_API_KEY,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            );
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    public function getFormattedHours($hour)
    {
        $hoursFormat = $this->scopeConfig->getValue(LSR::LS_STORES_OPENING_HOURS_FORMAT);
        if (empty($hour['temporary'])) {
            $formattedTime = "<td class='dayofweek'>" . $hour["day"] . "</td><td class='normal-hour'><span>" .
                date(
                    $hoursFormat,
                    strtotime($hour['normal']['open'])
                ) . ' - ' . date(
                    $hoursFormat,
                    strtotime($hour['normal']['close'])
                ) . '</span></td>';
        } elseif ($hour['normal']['open'] == StoreHourOpeningType::CLOSED) {
            $formattedTime = "<td class='dayofweek'>" . $hour['day'] . "</td><td class='normal-hour'>
            <span class='closed'>" . __("Closed") . '</span></td>';
        } else {
            if (empty($hour['normal'])) {
                $formattedTime = "<td class='dayofweek'>" . $hour['day'] . "</td><td><span class='special-hour'>" .
                    date(
                        $hoursFormat,
                        strtotime($hour['temporary']['open'])
                    ) . ' - ' . date(
                        $hoursFormat,
                        strtotime($hour['temporary']['close'])
                    ) . "<span class='special-label'>" . __('Special') . '</span></span></td>';
            } elseif (strtotime($hour['temporary']['open']) <= strtotime($hour['normal']['open'])) {
                $formattedTime = "<td class='dayofweek'>" . $hour['day'] . "</td><td><span class='special-hour'>" .
                    date(
                        $hoursFormat,
                        strtotime($hour['temporary']['open'])
                    ) . ' - ' . date(
                        $hoursFormat,
                        strtotime($hour['temporary']['close'])
                    ) . "<span class='special-label'>" . __('Special') . '</span></span>' .
                    "<br/><span class='normal-hour'>" .
                    date(
                        $hoursFormat,
                        strtotime($hour['normal']['open'])
                    ) . ' - ' . date(
                        $hoursFormat,
                        strtotime($hour['normal']['close'])
                    ) . '</span></td>';
            } else {
                $formattedTime = "<td class='dayofweek'>" . $hour['day'] . "</td><td><span class='normal-hour'>" .
                    date(
                        $hoursFormat,
                        strtotime($hour['normal']['open'])
                    ) . ' - ' . date(
                        $hoursFormat,
                        strtotime($hour['normal']['close'])
                    ) . '</span><br/>' . "<span class='special-hour'>" .
                    date(
                        $hoursFormat,
                        strtotime($hour['temporary']['open'])
                    ) . ' - ' . date(
                        $hoursFormat,
                        strtotime($hour['temporary']['close'])
                    ) . "<span class='special-label'>" . __('Special') . '</span></span></td>';
            }
        }
        return $formattedTime;
    }
}
