<?php
declare(strict_types=1);

namespace Ls\Customer\Test\Fixture;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\Fixture\DataFixtureInterface;

class OrderCreateFixture implements DataFixtureInterface
{
    private const DEFAULT_DATA = [];
    public $eventManager;
    public $state;

    /**
     * @param ManagerInterface $eventManager
     * @param State $state
     */
    public function __construct(
        ManagerInterface $eventManager,
        State $state
    ) {
        $this->eventManager                = $eventManager;
        $this->state                       = $state;
    }

    /**
     * Apply fixture data
     *
     * @param array $data
     * @return DataObject|null
     * @throws AlreadyExistsException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function apply(array $data = []): ?DataObject
    {
        $this->state->setAreaCode(Area::AREA_FRONTEND);
        $data     = array_merge(self::DEFAULT_DATA, $data);

        $order    = $data['order'];

        $this->eventManager->dispatch(
            'checkout_onepage_controller_success_action',
            ['order' => $order]
        );

        return $order;
    }
}
