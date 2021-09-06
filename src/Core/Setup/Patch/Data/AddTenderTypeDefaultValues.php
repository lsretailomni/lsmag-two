<?php

namespace Ls\Core\Setup\Patch\Data;

use \Ls\Core\Model\LSR;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Class for saving tender type default values in core config data
 */
class AddTenderTypeDefaultValues implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var Json
     */
    private $json;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param WriterInterface $configWriter
     * @param Json $json
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        WriterInterface $configWriter,
        Json $json
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->configWriter    = $configWriter;
        $this->json            = $json;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->addConfigData();
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     *  Adding config value
     *
     * @throws \Exception
     */
    private function addConfigData()
    {
        $configData = [
            'item1' => ["payment_method" => "checkmo", "tender_type" => "2"],
            'item2' => ["payment_method" => "giftcard", "tender_type" => "4"],
            'item3' => ["payment_method" => "loypoints", "tender_type" => "3"]
        ];

        $this->configWriter->save(
            LSR::LSR_PAYMENT_TENDER_TYPE_MAPPING,
            $this->json->serialize($configData)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
