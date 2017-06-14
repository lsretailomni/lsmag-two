<?php
namespace Ls\Core\Controller\Development;

use Ls\Replication\Api\CurrencyRepositoryInterface;
use Ls\Replication\Model\Currency;
use Ls\Replication\Model\CurrencyFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;

class Index extends Action
{
    /** @var PageFactory */
    protected $result_factory;
    /** @var CurrencyRepositoryInterface */
    protected $currency_repository;
    /** @var CurrencyFactory */
    protected $currency_factory;
    /** @var LoggerInterface */
    protected $logger;

    /**
     * Constructor
     *
     * @param Context                     $context
     * @param PageFactory                 $result_factory
     * @param CurrencyRepositoryInterface $currency_repository
     * @param CurrencyFactory             $currency_factory
     */
    public function __construct (
        Context $context,
        PageFactory $result_factory,
        CurrencyRepositoryInterface $currency_repository,
        CurrencyFactory $currency_factory,
        LoggerInterface $logger
    ) {
        $this->result_factory = $result_factory;
        $this->currency_repository = $currency_repository;
        $this->currency_factory = $currency_factory;
        $this->logger = $logger;

        parent::__construct( $context );
    }


    /**
     * @return ResultInterface|ResponseInterface
     * @throws NotFoundException
     */
    public function execute () {
        /** @var Currency $currency */
        $currency = $this->currency_factory->create();
        $this->logger->debug('$currency->getId() := ' . $currency->getId());
        $result = $this->currency_repository->save( $currency );
        $this->logger->debug('$currency->getId() := ' . $currency->getId());
        $this->logger->debug($result);

        return $this->result_factory->create();
//        $response = $this->response_factory->create();
//        $response->sendResponse();
    }
}
