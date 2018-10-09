<?php
namespace Ls\Omni\Console\Command;

use Composer\Autoload\ClassLoader;
use Ls\Omni\Client\Ecommerce\Entity\EnvironmentResponse;
use Ls\Omni\Code\ClassMapGenerator;
use Ls\Omni\Code\EntityGenerator;
use Ls\Omni\Code\OperationGenerator;
use Ls\Omni\Code\RestrictionGenerator;
use Ls\Omni\Console\Command;
use Ls\Omni\Service\Service;
use Ls\Omni\Service\Soap\Client;
use Magento\Framework\CurrencyInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class SetCurrencyOmni extends Command
{
    const COMMAND_NAME = 'omni:currency';

//    public function __construct(
//        \Magento\Framework\CurrencyInterface $currencyInterface
//    ) {
//        $this->currencyInterface = $currencyInterface;
//        parent::__construct();
//    }

    protected $appState;

    protected $environment;

    protected $scopeConfig;

    public function __construct(
        \Magento\Framework\App\State $appState,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Model\Product\Image\CacheFactory $imageCacheFactory,
        \Ls\Omni\Client\Ecommerce\Operation\Environment $environment,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $configInterface
    ) {
        $this->appState = $appState;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productRepository = $productRepository;
        $this->imageCacheFactory = $imageCacheFactory;

        $this->environment = $environment;
        $this->configInterface = $configInterface;

        parent::__construct();
    }

    protected function configure () {

        $this->setName( self::COMMAND_NAME )
            ->addOption( 'type', 't', InputOption::VALUE_REQUIRED, 'omni service type', 'ecommerce' )
            ->addOption( 'base', 'b', InputOption::VALUE_OPTIONAL, 'omni service base url' )
             ->setDescription( 'Generate class based on OMNI endpoints. Run this one first before replication generate' );

    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute ( InputInterface $input, OutputInterface $output ) {


        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $storeModel = $objectManager->get('\Magento\Store\Model\Store');
        $fileSystem = $objectManager->get('Magento\Framework\Filesystem');
        $mediaPath  =   $fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT)->getAbsolutePath();

        //$currencyInterface = $objectManager->get('\Magento\Directory\Model\CurrencyFactory');

        //dump($this->currencyInterface);

        #$val = $scopeConfig->getValue('ls_mag/service/selected_store', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        #$baseUrl =$scopeConfig->getValue('ls_mag/service/base_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);


        //dump($scopeConfig);
        #dump($val);
        #dump($baseUrl);

        //$x = $objectManager->get('\Magento\Catalog\Model\Locator\LocatorInterface');


        //$this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);

        //dump($storeManager->getStore()->getCurrentCurrency()->getCode());

        #dump($storeManager->getStore()->getCurrentCurrency()->setCode('MYR'));
        #dump($storeManager->getStore()->getCurrentCurrency()->setCurrencyCode('MYR'));

        //dump($storeManager->getStore()->getDefault->getCurrentCurrency()->setCurrencyCode('MYR'));

//        $defaultCode = $storeManager->getWebsite()->getDefaultStore()->getDefaultCurrency()->getCode();
//
//        dump($defaultCode);
//
//        $storeModel->setCurrentCurrencyCode("SGD");
//
//        dump($storeManager->getStore()->getCurrentCurrency()->getCode());
//
//        dump($storeModel->getCurrentCurrencyCode());
//
//        dump($storeModel->getBaseCurrencyCode());

        dump("Get environment variables from OMNI and set currency base : ");

        $response = $this->environment->execute();

        dump($response);

        $currency = $response->getEnvironmentResult()->getCurrency()->getId();

        $this->configInterface->saveConfig('currency/options/base', $currency,\Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);



    }

    /**
     * @param string $folder
     */
    private function clean ( $folder ) {

        $fs = new Filesystem();

        if ( $fs->exists( $folder ) ) $fs->remove( $folder );
        $fs->mkdir( $this->path( $folder, 'Operation' ) );
        $fs->mkdir( $this->path( $folder, 'Entity', 'Enum' ) );

        $ok = sprintf( 'done cleaning folder ( %1$s )', $fs->makePathRelative( $folder, getcwd() ) );
        $this->output->writeln( $ok );

    }
}
