<?php

namespace Ls\Omni\Block\Stores;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\Data;
use \Ls\Replication\Model\ResourceModel\ReplStore\Collection;
use \Ls\Replication\Model\ResourceModel\ReplStore\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;
use Psr\Log\LoggerInterface;

/**
 * Class Stores
 * @package Ls\Omni\Block\Stores
 */
class Stores extends Template
{
    /**
     * @var CollectionFactory
     */
    public $replStoreFactory;
    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;
    /**
     * @var Data
     */
    public $storeHoursHelper;
    /**
     * @var Data
     */
    public $logger;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * @param Template\Context $context
     * @param CollectionFactory $replStoreCollectionFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $storeHoursHelper
     * @param LSR $lsr
     * @param LoggerInterface $logger
     */
    public function __construct(
        Template\Context $context,
        CollectionFactory $replStoreCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        Data $storeHoursHelper,
        LSR $lsr,
        LoggerInterface $logger
    ) {
        $this->replStoreFactory = $replStoreCollectionFactory;
        $this->scopeConfig      = $scopeConfig;
        $this->storeHoursHelper = $storeHoursHelper;
        $this->lsr              = $lsr;
        $this->logger           = $logger;
        parent::__construct($context);
    }

    /**
     * @return Collection
     */
    public function getStores()
    {
        try {
            $collection = $this->replStoreFactory->create()
                ->addFieldToFilter('IsDeleted', 0)
                ->addFieldToFilter('scope_id', $this->lsr->getCurrentStoreId());

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

    /**
     * @param $hour
     * @return string
     */
    public function getFormattedHours($hour)
    {
        $formattedTime = "";
        $hoursFormat   = $this->scopeConfig->getValue(LSR::LS_STORES_OPENING_HOURS_FORMAT);
        if (isset($hour['closed'])) {
            $formattedTime = "<td class='dayofweek'>" . $hour['day'] . "</td><td class='normal-hour'>
            <span class='closed'>" . __("Closed") . '</span></td>';
        } elseif (isset($hour['normal']) && !isset($hour['temporary'])) {
            $formattedTime = "<td class='dayofweek'>" . $hour["day"] . "</td><td class='normal-hour'>";
            foreach ($hour['normal'] as $each) {
                $formattedTime .= "<span>" .
                    date(
                        $hoursFormat,
                        strtotime($each['open'])
                    ) . ' - ' . date(
                        $hoursFormat,
                        strtotime($each['close'])
                    ) . "</span><br/>";
            }

            $formattedTime .= "</td>";
        } elseif (isset($hour['normal']) && isset($hour['temporary'])) {
            if (strtotime($hour['temporary']['open']) <= strtotime($hour['normal'][0]['open'])) {
                $formattedTime = "<td class='dayofweek'>" . $hour['day'] . "</td><td><span class='special-hour'>" .
                    date(
                        $hoursFormat,
                        strtotime($hour['temporary']['open'])
                    ) . ' - ' . date(
                        $hoursFormat,
                        strtotime($hour['temporary']['close'])
                    ) . "<span class='special-label'>" . __('Special') . '</span></span>' .
                    "<br/>";
                foreach ($hour['normal'] as $each) {
                    $formattedTime .= "<span>" .
                        date(
                            $hoursFormat,
                            strtotime($each['open'])
                        ) . ' - ' . date(
                            $hoursFormat,
                            strtotime($each['close'])
                        ) . "</span><br/>";
                }

                $formattedTime .= "</td>";
            } else {
                $formattedTime = "<td class='dayofweek'>" . $hour["day"] . "</td><td class='normal-hour'>";
                foreach ($hour['normal'] as $each) {
                    $formattedTime .= "<span>" .
                        date(
                            $hoursFormat,
                            strtotime($each['open'])
                        ) . ' - ' . date(
                            $hoursFormat,
                            strtotime($each['close'])
                        ) . "</span><br/>";
                }
                $formattedTime .= "<span class='special-hour'>" .
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
