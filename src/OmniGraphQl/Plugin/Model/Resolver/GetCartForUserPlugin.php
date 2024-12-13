<?php

namespace Ls\OmniGraphQl\Plugin\Model\Resolver;

use \Ls\OmniGraphQl\Helper\DataHelper;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Interceptor to intercept GetCartForUser methods
 */
class GetCartForUserPlugin
{
    /**
     * @var DataHelper
     */
    public $dataHelper;

    /**
     * @param DataHelper $dataHelper
     */
    public function __construct(
        DataHelper $dataHelper
    ) {
        $this->dataHelper = $dataHelper;
    }

    /**
     * After plugin to set quote one_list_calculate in checkout session
     *
     * @param $subject
     * @param $result
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function afterExecute(
        $subject,
        $result
    ) {
        $this->dataHelper->setCurrentQuoteDataInCheckoutSession($result);

        return $result;
    }
}
