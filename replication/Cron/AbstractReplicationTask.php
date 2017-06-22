<?php
namespace Ls\Replication\Cron;

use Ls\Core\Helper\Data;
use Ls\Replication\Api\CurrencyRepositoryInterface;
use Ls\Replication\Model\Currency;
use Ls\Replication\Model\CurrencyFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Psr\Log\LoggerInterface;

class HeartbeatTask
{
    /** @var LoggerInterface */
    private $logger;
    /** @var ScopeConfigInterface */
    private $scope_config;
    /** @var Data */
    private $helper;
    /** @var CurrencyRepositoryInterface */
    private $currency_repository;
    /** @noinspection PhpUndefinedClassInspection */
    /** @var CurrencyFactory */
    private $currency_factory;
    /** @var ObjectManagerInterface */
    private $object_manager;


    public function __construct ( LoggerInterface $logger,
                                  ScopeConfigInterface $scope_config,
                                  Data $helper,
                                  ObjectManagerInterface $object_manager,
        /** @noinspection PhpUndefinedClassInspection */
                                  CurrencyFactory $currency_factory,
                                  CurrencyRepositoryInterface $currency_repository ) {
        $this->logger = $logger;
        $this->scope_config = $scope_config;
        $this->helper = $helper;
        $this->currency_repository = $currency_repository;
        $this->currency_factory = $currency_factory;
        $this->object_manager = $object_manager;
        $this->logger->debug( 'CONSTRUCT - DONE' );
    }

    public function execute () {

        $currency = $this->currency_factory->create();
        $this->logger->debug( get_class( $this->currency_repository ) );
        $this->logger->debug( get_class( $currency ) );
        /** @var Currency $currency */
        $currency->setCulture( 'culture' )
                 ->setAmountRoundingMethod( 'amount_rounding_method' )
                 ->setCurrencyCode( 'MXN' )
                 ->setCurrencyPrefix( 'prefix' )
                 ->setDecimalPlaces( 2 )
                 ->setDecimalSeparator( ',' )
                 ->setThousandSeparator( '.' );
//        $save_result = $currency_repository->save( $currency );
        $save_result = $this->currency_repository->save( $currency );
//        $this->currency_factory->save( $currency );
//        $this->currency_repository_factory->save( $currency );
        $this->logger->debug( "FINISH" );
        if ( $this->helper->enabled() ) {
            $this->logger->debug( "WORKING AS IT SHOULD" );
        } else {
            $this->logger->debug( "NO WAY JOSE" );
        }

        return $this;
    }
}

