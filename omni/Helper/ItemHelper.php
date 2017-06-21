<?php
namespace Ls\Omni\Helper;

use Ls\Replication\Model\BarcodeRepository;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\Helper\Context;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Operation;
use \Magento\Framework\Api\SearchCriteriaBuilder;

class ItemHelper extends \Magento\Framework\App\Helper\AbstractHelper {
    private $hashCache = array();

    protected $searchCriteriaBuilder;
    protected $barcodeRepository;
    protected $productRepository;

    public function __construct(
        Context $context,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        BarcodeRepository $barcodeRepository,
        ProductRepository $productRepository
    )
    {
        parent::__construct($context);
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->barcodeRepository = $barcodeRepository;
        $this->productRepository = $productRepository;
    }

    /**
     * @param Entity\Item         $item
     * @param Entity\Variant|null $variant
     *
     * @return string
     */
    public function sku ( Entity\Item $item, Entity\Variant $variant = NULL ) {

        if ( !isset( $this->hashCache[ $item->getProductGroupId() ] ) ) {
            $this->hashCache[ $item->getProductGroupId() ] = substr( crc32( $item->getProductGroupId() ), 0, 4 );
        }
        $parts = array( $this->hashCache[ $item->getProductGroupId() ], $item->getId() );
        if ( $variant != NULL ) {
            $parts[] = $variant->getId();
        }

        return join( '.', $parts );
    }

    /**
     * @param Entity\Item         $item
     * @param Entity\Variant|null $variant
     *
     * @return Entity\Barcode
     */
    public function barcode ( Entity\Item $item, Entity\Variant $variant = NULL ) {
        // TODO: test this once replication works

        $searchCriteriaBuilder = $this->searchCriteriaBuilder->addFilter(
            'item_id',
            $item->getId(),
            'eq'
        );

        // if there is a variant, add additional filter
        if ( !is_null( $variant ) ) {
            $searchCriteriaBuilder->addFilter( 'variant_id', $variant->getId() );
        }

        $result = $this->barcodeRepository->getList($searchCriteria->create());
        return $result->getItems()[0];
    }

    /**
     * @param            $id
     * @param bool|false $lite
     *
     * @return \LSR\Omni\Model\Omni\Domain\Item|false
     */
    public function get ( $id, $lite = FALSE ) {

        $result = FALSE;

        // TODO: maybe add in cache again?

        #$helper = Mage::helper( 'lsr/cache' );
        #$cache_key = $helper->cache_key( LSR::CACHE_DOMAIN_ITEM_PREFIX, $id );
        /** @var \LSR\Omni\Model\Omni\Domain\Item $cached */
        #$cached = $helper->get( $cache_key );

        #if ( $cached instanceof Entity\Item ) {

            #$result = $cached;
        #} else {
        $entity = new Entity\ItemGetById();
        $entity->setItemId( $id );
        /** @var \LSR\Omni\Model\Omni\Domain\ItemGetByIdResponse $response */
        $request = new Operation\ItemGetById();
        $response = $request->execute($entity);

        if ( $response && !is_null( $response->getItemGetByIdResult() ) ) {
            $item = $response->getItemGetByIdResult();
            // store the item returned by item-getbyid for 30 minutes
            #$helper->set( $item, $cache_key, 1800 );
            $result = $item;
            }
        #}

        return $lite && $result
            ? $this->lite( $result )
            : $result;
    }

    /**
     * @param \LSR\Omni\Model\Omni\Domain\Item $item
     *
     * @return \LSR\Omni\Model\Omni\Domain\Item
     */
    public function lite ( Entity\Item $item ) {

        // TODO: configuration hook lists attributes of the LSR_Omni_Model_Omni_Domain_Item considered LITE
        return ( new Entity\Item )
            ->setId( $item->getId() )
            ->setPrice( $item->getPrice() )
            ->setAllowedToSell( $item->getAllowedToSell() );
    }

    /**
     * Get the Magento product corresponding to the Omni item/variant
     * @param Entity\Item         $item
     * @param Entity\Variant|null $variant
     *
     * @return \Magento\Catalog\Model\Product]null
     */
    public function product ( Entity\Item $item, Entity\Variant $variant = NULL ) {
        // TODO: test this once replication works
        $itemId = is_null( $variant )
            ? $item->getId()
            : join( '.', array( $item->getId(), $variant->getId() ));
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('lsr_id',$itemId, 'like')->create();
        $productList = $this->productRepository->getList($searchCriteria);
        return $productList[0];
    }

    /**
     * @param Entity\Item $item
     *
     * @return Entity\UOM|null
     */
    public function uom ( Entity\Item $item ) {

        /** @var Entity\UOM $uom */
        $uoms = $item->getUOMs();
        if (!is_null($uoms) && $uoms instanceof Entity\ArrayOfUOM) {
            $uom = $uoms->getUOM();
        } else {
            $uom = ( new Entity\UOM() )
                ->setId( $uom->getId() )
                ->setDecimals( $uom->getDecimals() )
                ->setItemId( $uom->getItemId() )
                ->setQtyPerUom( $uom->getQtyPerUom() );
        }

        return $uom;
    }
}