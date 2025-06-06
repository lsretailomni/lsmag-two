<?php
declare(strict_types=1);

namespace Ls\Replication\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class DecodeText extends Column
{
    /**
     * Decode given tex from base64
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        $field = $this->getData('name');

        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item[$field])) {
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction
                    $item[$field] = base64_decode($item[$field]);
                }
            }
        }

        return $dataSource;
    }
}
