<?php
declare(strict_types=1);

namespace Ls\Omni\Helper;

use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;
use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\StoreHourOpeningType;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\StoreHourCalendarType;
use \Ls\Omni\Client\Ecommerce\Entity\GetStores_GetStores;
use \Ls\Omni\Client\ResponseInterface;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Operation;
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
     * @return array|bool|GetStores_GetStores|DataObject|mixed
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
                // @codingStandardsIgnoreStart
                $webStoreOperation = $this->createInstance(Operation\GetStores_GetStores::class);
                $webStoreOperation->setOperationInput(
                    ['storeGetType' => '0', 'searchText' => $webStore, 'includeDetail' => false]
                );
                // @codingStandardsIgnoreEnd
                $response = current($webStoreOperation->execute()->getRecords());

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
     * Get all stores
     *
     * @param $webStoreId
     * @return array|Entity\ArrayOfStore|Entity\StoresGetAllResponse|Entity\StoresGetResponse|ResponseInterface|null
     * @throws NoSuchEntityException
     */
    public function getAllStores($webStoreId)
    {
        $response = [];
        $cacheId        = LSR::STORES;
        $cachedResponse = $this->cacheHelper->getCachedContent($cacheId);

        if ($cachedResponse) {
            $response = $cachedResponse;
        } else {
            $baseUrl  = $this->lsr->getStoreConfig(LSR::SC_SERVICE_BASE_URL, $webStoreId);
            // @codingStandardsIgnoreStart
            if (version_compare($this->lsr->getOmniVersion(), '2023.01', '>')) {
                $request = new Entity\StoresGet();
                $request->setIncludeDetails(true);
                $operation = new Operation\StoresGet($baseUrl);
            } else {
                $request   = new Entity\StoresGetAll();
                $operation = new Operation\StoresGetAll($baseUrl);
            }
            // @codingStandardsIgnoreEnd
            try {
                $response = $operation->execute($request);

                if (!empty($response)) {
                    $this->cacheHelper->persistContentInCache(
                        $cacheId,
                        $response,
                        [Type::CACHE_TAG],
                        86400
                    );
                }
            } catch (Exception $e) {
                $this->_logger->error($e->getMessage());
            }
        }

        return $response ? $response->getResult() : $response;
    }

    /**
     * Getting store hours
     *
     * @param array $storeHours
     * @return array
     * @throws NoSuchEntityException
     */
    // @codingStandardsIgnoreStart
    public function getStoreOrderingHours($storeHours, $calendarType)
    {
        if (empty($storeHours)) {
            $store      = $this->getStore($this->lsr->getStoreId());
            $storeHours = $store->getStoreHours();
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
            foreach ($storeHours as $storeHour) {
                if ((!$calendarType && $storeHour->getCalendarType() == $this->getRetailCalendarType()) ||
                    ($calendarType && $storeHour->getCalendarType() == $calendarType)) {
                    if ($storeHour->getDayOfWeek() == $currentDayOfWeek) {
                        if ($this->dataHelper->checkDateValidity($current, $storeHour)) {
                            if ($storeHour->getType() == StoreHourOpeningType::NORMAL) {
                                $dateTimSlots[$current][StoreHourOpeningType::NORMAL] =
                                    [
                                        "open"  => $this->formatTime(
                                            $storeHour->getOpenFrom() ??
                                            '0001-01-01T00:00:00Z'
                                        ),
                                        "close" => $this->formatTime(
                                            $storeHour->getOpenTo() ??
                                            '0001-01-01T00:00:00Z'
                                        )
                                    ];
                            } elseif ($storeHour->getType() == StoreHourOpeningType::TEMPORARY) {
                                $dateTimSlots[$current][StoreHourOpeningType::TEMPORARY] =
                                    [
                                        "open"  => $this->formatTime(
                                            $storeHour->getOpenFrom() ??
                                            '0001-01-01T00:00:00Z'
                                        ),
                                        "close" => $this->formatTime(
                                            $storeHour->getOpenTo() ??
                                            '0001-01-01T00:00:00Z'
                                        )
                                    ];
                            } else {
                                $dateTimSlots[$current][StoreHourOpeningType::CLOSED] =
                                    [
                                        "open"  => $this->formatTime(
                                            $storeHour->getOpenFrom() ??
                                            '0001-01-01T00:00:00Z'
                                        ),
                                        "close" => $this->formatTime(
                                            $storeHour->getOpenTo() ??
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
     * @param array $storeHours
     * @return array
     * @throws NoSuchEntityException
     */
    public function formatDateTimeSlotsValues($storeHours, $calendarType = null)
    {
        $results = [];
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            $options = $this->getDateTimeSlotsValues($storeHours, $calendarType);
            if (!empty($options)) {
                foreach ($options as $key => $option) {
                    $results[$key] = $option[StoreHourOpeningType::NORMAL];
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
     * @param array $storeHours
     * @return array
     * @throws Exception
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

                if ($type == StoreHourOpeningType::CLOSED) {
                    if (array_key_exists(StoreHourOpeningType::NORMAL, $dateTimeSlots[$date])) {
                        $dateTimeSlots[$date][StoreHourOpeningType::NORMAL] = array_diff(
                            $dateTimeSlots[$date][StoreHourOpeningType::NORMAL],
                            $dateTimeSlots[$date][StoreHourOpeningType::CLOSED]
                        );
                    }
                }

                if ($type == StoreHourOpeningType::TEMPORARY) {
                    if (array_key_exists(StoreHourOpeningType::NORMAL, $dateTimeSlots[$date])) {
                        $dateTimeSlots[$date][StoreHourOpeningType::NORMAL] =
                            $dateTimeSlots[$date][StoreHourOpeningType::TEMPORARY];
                    }
                }
            }

            if ($this->getCurrentDate() == $date &&
                isset($dateTimeSlots[$date]) &&
                isset($dateTimeSlots[$date][StoreHourOpeningType::NORMAL])
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
            return StoreHourCalendarType::RECEIVING;
        }

        return StoreHourCalendarType::REST_ORDER_TAKING;
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
            $intervalsCollection[] = $this->dateTime->date($this->pickupTimeFormat, strtotime($interval));
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
