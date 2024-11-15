<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Fixture;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Fixture\DataFixtureInterface;
use Magento\TestFramework\Helper\Bootstrap;
use ReflectionException;

class FlatDataReplication implements DataFixtureInterface
{
    private const DEFAULT_DATA = [];

    /** @var StoreManagerInterface */
    public $storeManager;

    /**
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
    }

    /**
     * Apply fixture data
     *
     * @param array $data
     * @return DataObject|null
     * @throws AlreadyExistsException
     * @throws LocalizedException
     * @throws NoSuchEntityException|ReflectionException
     */
    public function apply(array $data = []): ?DataObject
    {
        $data = array_merge(self::DEFAULT_DATA, $data);

        if (isset($data['job_url']) && isset($data['scope'])) {
            if ($data['scope'] == ScopeInterface::SCOPE_WEBSITE) {
                $storeData = $this->storeManager->getWebsite();
            } else {
                $storeData = $this->storeManager->getStore();
            }

            $job = Bootstrap::getObjectManager()->create($data['job_url']);
            $job->executeManually($storeData);
        }

        return new DataObject();
    }
}
