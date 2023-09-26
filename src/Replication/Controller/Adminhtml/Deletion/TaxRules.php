<?php

namespace Ls\Replication\Controller\Adminhtml\Deletion;

use Magento\Framework\App\ResponseInterface;

/**
 * Controller to delete tax rules
 */
class TaxRules extends AbstractReset
{
    /** List of all the Magento Tax Rules tables */
    public const TAX_RULES_TABLES = [
        'tax_calculation_rule',
        'tax_calculation',
        'tax_calculation_rate',
        'tax_calculation_rate_title'
    ];

    /**
     * Remove Tax Rules
     *
     * @return ResponseInterface
     */
    public function execute()
    {
        $this->truncateAllGivenTables(self::TAX_RULES_TABLES);
        $this->updateAllGivenTablesToUnprocessed(self::LS_TAX_RELATED_TABLES, []);

        $this->messageManager->addSuccessMessage(__('Tax Rules deleted successfully.'));

        return $this->_redirect('adminhtml/system_config/edit/section/ls_mag');
    }
}
