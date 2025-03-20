<?php
declare(strict_types=1);

namespace Ls\Customer\Test\Fixture;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Model\AddressRegistry;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\Fixture\DataFixtureInterface;

class CustomerAddressFixture implements DataFixtureInterface
{
    private const DEFAULT_DATA = [
        'entity_id' => 1,
        'attribute_set_id' => 2,
        'telephone' => 4145700,
        'postcode' => 201,
        'country_id' => 'US',
        'city' => 'Kopavogur',
        'company' => 'Ls Retail',
        'street' => 'LS Retail ehf.',
        'lastname' => 'Test',
        'firstname' => 'Test',
        'parent_id' => 1,
        'region_id' => 1,
        'default_billing' => 1,
        'default_shipping' => 1
    ];

    /** @var AddressInterfaceFactory */
    public $addressFactory;

    /** @var AddressRepositoryInterface */
    public $addressRepository;

    /**
     * @var AddressRegistry
     */
    public $addressRegistry;

    public function __construct(
        AddressInterfaceFactory $addressFactory,
        AddressRepositoryInterface $addressRepository,
        AddressRegistry $addressRegistry
    ) {
        $this->addressFactory = $addressFactory;
        $this->addressRepository = $addressRepository;
        $this->addressRegistry = $addressRegistry;
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
        $data = array_merge(self::DEFAULT_DATA, $data);

        $address = $this->addressFactory->create();

        foreach ($data as $index => $value) {
            $address->setData($index, $value);
        }

        $this->addressRepository->save($address);

        return $this->addressRegistry->retrieve($address->getId());
    }
}
