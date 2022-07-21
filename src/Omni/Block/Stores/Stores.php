<?php

namespace Ls\Omni\Block\Stores;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\StoreHourOpeningType;
use \Ls\Omni\Helper\Data;
use \Ls\Replication\Model\ResourceModel\ReplStore\Collection;
use \Ls\Replication\Model\ResourceModel\ReplStore\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;
use Psr\Log\LoggerInterface;

/**
 * Stores page block class
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
     * Stores Constructor.
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
     * Get Store Hours given store_id
     *
     * @param string $storeId
     * @return array
     */
    public function getStoreHours($storeId)
    {
        $storeHours = [];
        try {
            $storeHours = $this->storeHoursHelper->getStoreHours($storeId);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $storeHours;
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
     * Get formatted hours html
     *
     * @param array $hour
     * @return string
     */
    public function getFormattedHours($hour)
    {
        $formattedTime = "";
        $hoursFormat   = $this->scopeConfig->getValue(LSR::LS_STORES_OPENING_HOURS_FORMAT);

        $hour = $this->removeNormalEntryIfSameCloseEntry($hour);

        foreach ($hour as $i => $entry) {
            $entryTimeStampOpen = strtotime($entry['open']);
            $entryTimeStampClose = strtotime($entry['close']);

            if ($i === 0) {
                $formattedTime .= "<td class='dayofweek'>" . $entry["day"] . "</td><td class='normal-hour'>";
            }

            if ($entry['type'] == StoreHourOpeningType::NORMAL) {
                $formattedTime .= "<span>" .
                    date(
                        $hoursFormat,
                        $entryTimeStampOpen
                    ) . ' - ' . date(
                        $hoursFormat,
                        $entryTimeStampClose
                    ) . "</span><br/>";
            } elseif ($entry['type'] == StoreHourOpeningType::TEMPORARY) {
                $formattedTime .= "<span class='special-hour'>" .
                    date(
                        $hoursFormat,
                        $entryTimeStampOpen
                    ) . ' - ' . date(
                        $hoursFormat,
                        $entryTimeStampClose
                    ) . "<span class='special-label'>" . __('Special') . '</span></span>' . "<br/>";

            } else {
                if (count($hour) == 1) {
                    $formattedTime .= "<span class='closed'>".
                    "<span class='closed-label single'>" . __('Closed') . '</span></span>' . "<br/>";
                } else {
                    $formattedTime .= "<span class='closed'>" .
                        date(
                            $hoursFormat,
                            $entryTimeStampOpen
                        ) . ' - ' . date(
                            $hoursFormat,
                            $entryTimeStampClose
                        ) . "<span class='closed-label'>" . __('Closed') . '</span></span>' . "<br/>";
                }
            }

            if ($i == count($hour) - 1) {
                $formattedTime .= "</td>";
            }
        }

        return $formattedTime;
    }

    /**
     * Remove normal entry if same closed entry
     *
     * @param array $hour
     * @return mixed
     */
    public function removeNormalEntryIfSameCloseEntry($hour)
    {
        $normalIndex = $closedIndex = null;

        foreach ($hour as $i => $entry) {
            if ($entry['type'] == StoreHourOpeningType::NORMAL) {
                $normalTimeStampOpen  = strtotime($entry['open']);
                $normalTimeStampClose = strtotime($entry['close']);
                $normalIndex = $i;
            }

            if ($entry['type'] == StoreHourOpeningType::CLOSED) {
                $closedTimeStampOpen  = strtotime($entry['open']);
                $closedTimeStampClose = strtotime($entry['close']);
                $closedIndex = $i;
            }
        }

        if (isset($normalIndex) &&
            isset($closedIndex) &&
            $normalTimeStampOpen == $closedTimeStampOpen &&
            $normalTimeStampClose == $closedTimeStampClose
        ) {
            unset($hour[$normalIndex]);
            $hour = array_values($hour);
        }

        return $hour;
    }
}
