<?php
declare(strict_types=1);

namespace Ls\Omni\Helper;

use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\CentralEcommerce\Entity\GetStores_GetStores;
use \Ls\Omni\Client\CentralEcommerce\Entity\RootGetStoreOpeningHours;
use \Ls\Omni\Client\CentralEcommerce\Operation;
use \Ls\Omni\Model\Cache\Type;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Store Helper function
 *
 */
class StoreHelper extends AbstractHelperOmni
{
    /**
     * @var $pickupDateFormat
     */
    public $pickupDateFormat;

    /**
     * @var $pickupTimeFormat
     */
    public $pickupTimeFormat;

    /**
     * Getting sales type
     *
     * @param string $websiteId
     * @return mixed|null
     */
    public function getSalesType(string $websiteId = '')
    {
        $storeDetails = $this->getStore($websiteId);

        return $storeDetails->getLscSalesType();
    }

    /**
     * Getting Store By id
     *
     * @param string $websiteId
     * @param string|null $webStore
     * @return array|GetStores_GetStores|DataObject
     */
    public function getStore(string $websiteId = '', ?string $webStore = null)
    {
        $response = [];

        if ($webStore == null) {
            $webStore = $this->lsr->getWebsiteConfig(LSR::SC_SERVICE_STORE, $websiteId);
        }

        try {
            $cacheId = LSR::STORE . $webStore;
            $cachedResponse = $this->cacheHelper->getCachedContent($cacheId);

            if ($cachedResponse) {
                $response = $cachedResponse;
            } else {
                $response = $this->fetchStoresDataFromCentral($webStore);
                if (!empty($response)) {
                    $this->cacheHelper->persistContentInCache(
                        $cacheId,
                        $response,
                        [Type::CACHE_TAG],
                        86400
                    );
                }
            }
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return $response;
    }

    /**
     * Getting all stores from central
     *
     * @return array|GetStores_GetStores|DataObject
     */
    public function getAllStoresFromCentral()
    {
        $response = [];

        try {
            $cacheId = LSR::STORES . $this->lsr->getCurrentWebsiteId();
            $cachedResponse = $this->cacheHelper->getCachedContent($cacheId);

            if ($cachedResponse) {
                $response = $cachedResponse;
            } else {
                $response = $this->fetchStoresDataFromCentral('', '1', true);
                if (!empty($response)) {
                    $this->cacheHelper->persistContentInCache(
                        $cacheId,
                        $response,
                        [Type::CACHE_TAG],
                        86400
                    );
                }
            }
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return $response;
    }

    /**
     * Fetch stores data from central based on parameters
     *
     * @param string $searchText
     * @param string $storeGetType
     * @param bool $includeDetails
     * @return false|mixed
     */
    public function fetchStoresDataFromCentral(
        string $searchText = '',
        string $storeGetType = '1',
        bool $includeDetails = false
    ) {
        // @codingStandardsIgnoreStart
        $webStoreOperation = $this->createInstance(Operation\GetStores_GetStores::class);
        $webStoreOperation->setOperationInput(
            ['storeGetType' => $storeGetType, 'searchText' => $searchText, 'includeDetail' => $includeDetails]
        );
        // @codingStandardsIgnoreEnd
        return current($webStoreOperation->execute()->getRecords());
    }

    /**
     * Getting store hours
     *
     * @param RootGetStoreOpeningHours $storeHours
     * @param ?int $calendarType
     * @return array
     * @throws NoSuchEntityException
     */
    // @codingStandardsIgnoreStart
    public function getStoreOrderingHours($storeHours, $calendarType)
    {
        if (empty($storeHours)) {
            $webStore = $this->lsr->getWebsiteConfig(LSR::SC_SERVICE_STORE, $this->lsr->getCurrentWebsiteId());
            $storeHours = $this->dataHelper->getStoreHours($webStore);
        }
        $today                  = $this->getCurrentDate();
        $this->pickupDateFormat = $this->lsr->getStoreConfig(LSR::PICKUP_DATE_FORMAT);
        $this->pickupTimeFormat = $this->lsr->getStoreConfig(LSR::PICKUP_TIME_FORMAT);
        $dateTimSlots           = [];

        for ($count = 0; $count < 7; $count++) {
            $current          = $this->dateTime->date(
                $this->pickupDateFormat,
                strtotime($today) + ($count * 86400)
            );
            $currentDayOfWeek = $this->dateTime->date('w', strtotime($current));

            if (!is_array($storeHours)) {
                $storeHours = $storeHours->getRetailcalendarline();
            }

            foreach ($storeHours ?? [] as $storeHour) {
                if ((!$calendarType && $storeHour->getCalendarType() == $this->getRetailCalendarType()) ||
                    ($calendarType && $storeHour->getCalendarType() == $calendarType)) {

                    if ($storeHour->getDayNo() == $currentDayOfWeek) {
                        if ($this->dataHelper->checkDateValidity($current, $storeHour)) {
                            if ($storeHour->getLineType() == 0) {
                                $dateTimSlots[$current]['Normal'] =
                                    [
                                        "open"  => $this->formatTime(
                                            $storeHour->getTimeFrom() ??
                                            '0001-01-01T00:00:00Z'
                                        ),
                                        "close" => $this->formatTime(
                                            $storeHour->getTimeTo() ??
                                            '0001-01-01T00:00:00Z'
                                        )
                                    ];
                            } elseif ($storeHour->getLineType() == 1) {
                                $dateTimSlots[$current]['Temporary'] =
                                    [
                                        "open"  => $this->formatTime(
                                            $storeHour->getTimeFrom() ??
                                            '0001-01-01T00:00:00Z'
                                        ),
                                        "close" => $this->formatTime(
                                            $storeHour->getTimeTo() ??
                                            '0001-01-01T00:00:00Z'
                                        )
                                    ];
                            } else {
                                $dateTimSlots[$current]['Closed'] =
                                    [
                                        "open"  => $this->formatTime(
                                            $storeHour->getTimeFrom() ??
                                            '0001-01-01T00:00:00Z'
                                        ),
                                        "close" => $this->formatTime(
                                            $storeHour->getTimeTo() ??
                                            '0001-01-01T00:00:00Z'
                                        )
                                    ];
                            }
                        }
                    }
                }
            }
        }

        return $dateTimSlots;
    }
    // @codingStandardsIgnoreEnd

    /**
     * For getting date and timeslots option
     *
     * @param RootGetStoreOpeningHours|array $storeHours
     * @param ?string $calendarType
     * @return array
     * @throws GuzzleException
     * @throws NoSuchEntityException
     */
    public function formatDateTimeSlotsValues($storeHours, $calendarType = null)
    {
        $results = [];
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            $options = $this->getDateTimeSlotsValues($storeHours, $calendarType);
            if (!empty($options)) {
                foreach ($options as $key => $option) {
                    $results[$key] = $option['Normal'];
                }
            }
        }

        return $results;
    }

    /**
     * Format time
     *
     * @param string $time
     * @return string
     */
    public function formatTime($time)
    {
        return $this->dateTime->date(
            $this->pickupTimeFormat,
            strtotime($time)
        );
    }

    /**
     * Get date time slots
     *
     * @param RootGetStoreOpeningHours $storeHours
     * @param ?int $calendarType
     * @return array
     * @throws NoSuchEntityException
     */
    public function getDateTimeSlotsValues($storeHours, $calendarType)
    {
        $dateTimeSlots      = [];
        $storeOrderingHours = $this->getStoreOrderingHours($storeHours, $calendarType);
        $timeInterval       = $this->lsr->getStoreConfig(LSR::PICKUP_TIME_INTERVAL);

        foreach ($storeOrderingHours as $date => $storeOrderHour) {
            foreach ($storeOrderHour as $type => $value) {
                $typeValues = $this->applyPickupTimeInterval(
                    $value['open'],
                    $value['close'],
                    $timeInterval,
                    $this->getCurrentDate() == $date
                );

                if (!empty($typeValues)) {
                    $dateTimeSlots[$date][$type] = $typeValues;
                }

                if ($type == 'Closed') {
                    if (array_key_exists('Normal', $dateTimeSlots[$date])) {
                        $dateTimeSlots[$date]['Normal'] = array_diff(
                            $dateTimeSlots[$date]['Normal'],
                            $dateTimeSlots[$date]['Closed']
                        );
                    }
                }

                if ($type == 'Temporary') {
                    if (array_key_exists('Normal', $dateTimeSlots[$date])) {
                        $dateTimeSlots[$date]['Normal'] =
                            $dateTimeSlots[$date]['Temporary'];
                    }
                }
            }

            if ($this->getCurrentDate() == $date &&
                isset($dateTimeSlots[$date]) &&
                isset($dateTimeSlots[$date]['Normal'])
            ) {
                $dateTimeSlots = $this->replaceKey($dateTimeSlots, $date, 'Today');
            }
        }

        return $dateTimeSlots;
    }

    /**
     * Replace Key
     *
     * @param array $orginalArray
     * @param String $oldKey
     * @param String $newKey
     * @return array
     */
    public function replaceKey($orginalArray, $oldKey, $newKey)
    {
        if (array_key_exists($oldKey, $orginalArray)) {
            $keys                               = array_keys($orginalArray);
            $keys[array_search($oldKey, $keys)] = $newKey;
            return array_combine($keys, $orginalArray);
        }

        return $orginalArray;
    }

    /**
     * Current date
     *
     * @return false|string
     */
    public function getCurrentDate()
    {
        return $this->dateTime->gmtDate($this->pickupDateFormat);
    }

    /**
     * Apply pickup time interval
     *
     * @param string $startTime
     * @param string $endTime
     * @param string $interval
     * @param int $filterCurrentDate
     * @return array
     * @throws Exception
     */
    public function applyPickupTimeInterval($startTime, $endTime, $interval, $filterCurrentDate)
    {
        $startTime          = $this->dateTime->date(
            $this->pickupTimeFormat,
            ceil(strtotime($startTime) / ($interval * 60)) * ($interval * 60)
        );
        $endTime            = $this->dateTime->date(
            $this->pickupTimeFormat,
            floor(strtotime($endTime) / ($interval * 60)) * ($interval * 60)
        );
        $startTimeSeconds   = strtotime($startTime);
        $endTimeSeconds     = strtotime($endTime);
        $currentTime        = $this->dateTime->date(
            $this->pickupTimeFormat,
            ceil(strtotime($this->dateTime->gmtDate($this->pickupTimeFormat)) / ($interval * 60)) * ($interval * 60)
        );
        $currentTime        = $this->timezone->date(new \DateTime($currentTime))->format($this->pickupTimeFormat);
        $currentTimeSeconds = strtotime($currentTime);
        if ($filterCurrentDate) {
            if ($currentTimeSeconds > $startTimeSeconds && $currentTimeSeconds > $endTimeSeconds) {
                return [];
            }

            if ($currentTimeSeconds >= $startTimeSeconds) {
                $startTime = $currentTime;
            }

            if ($startTime == $endTime) {
                return [$startTime];
            }
        }

        return $this->getIntervalsGivenPeriodAndInterval($startTime, $endTime, $interval);
    }

    /**
     * Get calendar type
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getRetailCalendarType()
    {
        if ($this->lsr->getCurrentIndustry($this->lsr->getCurrentStoreId()) == LSR::LS_INDUSTRY_VALUE_RETAIL) {
            return 2;
        }

        return 3;
    }

    /**
     * Get intervals given period and
     *
     * @param string $startTime
     * @param string $endTime
     * @param string $interval
     * @return array
     */
    public function getIntervalsGivenPeriodAndInterval($startTime, $endTime, $interval)
    {
        $intervalsCollection = [];

        if (strtotime($endTime) > strtotime($startTime)) {
            $intervals = $this->getTimeSlicesGivenRangesAndInterval($startTime, $endTime, $interval);
            $this->loopThroughCollection($intervals, $intervalsCollection);

        } else {
            $intervals1 = $this->getTimeSlicesGivenRangesAndInterval($startTime, '11:59 PM', $interval);
            $intervals2 = $this->getTimeSlicesGivenRangesAndInterval('12:00 AM', $endTime, $interval);

            $this->loopThroughCollection($intervals1, $intervalsCollection);
            $this->loopThroughCollection($intervals2, $intervalsCollection);
        }

        return $intervalsCollection;
    }

    /**
     * Loop through collection
     *
     * @param mixed $intervals
     * @param array $intervalsCollection
     * @return void
     */
    public function loopThroughCollection($intervals, &$intervalsCollection)
    {
        foreach ($intervals as $interval) {
            $intervalsCollection[] = $this->dateTime->date($this->pickupTimeFormat, $interval->toTimeString());
        }
    }

    /**
     * Get time slices given ranges and interval
     *
     * @param string $startTime
     * @param string $endTime
     * @param string $interval
     * @return CarbonPeriod
     */
    public function getTimeSlicesGivenRangesAndInterval($startTime, $endTime, $interval)
    {
        $today = \Carbon\Carbon::today();

        return CarbonInterval::minutes($interval)->toPeriod($startTime, $endTime);
    }

    /**
     * Get store data by store id
     *
     * @param $storeId
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getStoreDataByStoreId($storeId)
    {
        return $this->storeCollectionFactory
            ->create()
            ->addFieldToFilter('scope_id', $this->lsr->getCurrentWebsiteId())
            ->addFieldToFilter('nav_id', $storeId)
            ->getFirstItem();
    }
}
