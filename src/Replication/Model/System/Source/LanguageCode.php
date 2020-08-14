<?php

namespace Ls\Replication\Model\System\Source;

use \Ls\Replication\Model\ReplDataTranslationLangCode;
use \Ls\Replication\Api\ReplDataTranslationLangCodeRepositoryInterface as ReplDataTranslationLangCodeRepository;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Model\ReplDataTranslationLangCodeSearchResults;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class LanguageCode
 * @package Ls\Replication\Model\System\Source
 */
class LanguageCode implements OptionSourceInterface
{

    /**
     * @var ReplDataTranslationLangCodeRepository
     */
    protected $replDataTranslationLangCodeRepository;

    /**
     * @var ReplicationHelper
     */
    protected $replicationHelper;

    /**
     * @var Http
     */
    private $request;

    /**
     * LanguageCode constructor.
     * @param ReplDataTranslationLangCodeRepository $replDataTranslationLangCodeRepository
     * @param ReplicationHelper $replicationHelper
     * @param Http $request
     */
    public function __construct(
        ReplDataTranslationLangCodeRepository $replDataTranslationLangCodeRepository,
        ReplicationHelper $replicationHelper,
        Http $request
    ) {
        $this->replDataTranslationLangCodeRepository = $replDataTranslationLangCodeRepository;
        $this->replicationHelper                     = $replicationHelper;
        $this->request                               = $request;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $storeId       = $this->request->getParam('store', 0);
        $scopeIdFilter = [
            ['field' => 'scope_id', 'value' => $storeId, 'condition_type' => 'eq']
        ];
        $langCodes[]   = ['value' => '', 'label' => __('Default')];
        $criteria      = $this->replicationHelper->buildCriteriaForDirect($scopeIdFilter);
        /** @var ReplDataTranslationLangCodeSearchResults $replData */
        $replData = $this->replDataTranslationLangCodeRepository->getList($criteria);
        /** @var ReplDataTranslationLangCode $langCode */
        foreach ($replData->getItems() as $langCode) {
            $langCodes[] = ['value' => $langCode->getCode(), 'label' => $langCode->getCode()];
        }
        return $langCodes;
    }
}
