<?php

namespace Ls\Omni\Plugin\Checkout\Block\Checkout\AttributeMerger;

/**
 * Class StreetPlugin
 * @package Ls\Omni\Plugin\Checkout\Block\Checkout\AttributeMerger
 */
class StreetPlugin
{
    /**
     * @param \Magento\Checkout\Block\Checkout\AttributeMerger $subject
     * @param $result
     * @return mixed
     */
    public function afterMerge(\Magento\Checkout\Block\Checkout\AttributeMerger $subject, $result)
    {
        if (array_key_exists('street', $result)) {
            $result['street']['children'][0]['placeholder'] = __('Flat No/House No/Building No');
            $result['street']['children'][1]['placeholder'] = __('Street Name/Landmark');
            $result['street']['children'][0]['validation'] = [
                'required-entry' => true,
                'min_text_length' => 1,
                'max_text_length' => 50
            ];
            $result['street']['children'][1]['validation'] = [
                'required-entry' => false,
                'min_text_length' => 1,
                'max_text_length' => 50
            ];
            $result['city']['validation'] = [
                'max_text_length' => 30
            ];
            $result['region']['validation'] = [
                'max_text_length' => 30
            ];
            $result['telephone']['validation'] = [
                'required-entry' => true,
                'max_text_length' => 30
            ];
            $result['firstname']['validation'] = [
                'required-entry' => true,
                'max_text_length' => 24
            ];
            $result['lastname']['validation'] = [
                'required-entry' => true,
                'max_text_length' => 24
            ];
            $result['postcode']['validation'] = [
                'required-entry' => true,
                'max_text_length' => 20
            ];
        }
        return $result;
    }
}
