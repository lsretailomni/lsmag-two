<?php

namespace Ls\Replication\Model\System\Source;

use \Ls\Core\Model\LSR;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Serialize\Serializer\Json;

class LanguageCode implements OptionSourceInterface
{
    /**
     * @param Http $request
     * @param LSR $lsr
     * @param Json $jsonEncoder
     */
    public function __construct(
        public Http $request,
        public LSR $lsr,
        public Json $jsonEncoder
    ) {
    }

    /**
     * Get list of options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $storeId       = $this->request->getParam('store', 0);
        $serializedStr = $this->lsr->getStoreConfig(
            LSR::SC_STORE_REPLICATED_DATA_TRANSLATION_LANG_CODE,
            $storeId
        );
        $langCodes[] = ['value' => 'Default', 'label' => __('Default')];

        if (!empty($serializedStr)) {
            $records = $this->jsonEncoder->unserialize($serializedStr);

            foreach ($records as $langCode) {
                $langCodes[] = ['value' => $langCode['Language Code'], 'label' => $langCode['Language Name']];
            }
        }

        return $langCodes;
    }
}
