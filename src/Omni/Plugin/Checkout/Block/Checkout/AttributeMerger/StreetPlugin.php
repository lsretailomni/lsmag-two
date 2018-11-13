<?php
namespace Ls\Omni\Plugin\Checkout\Block\Checkout\AttributeMerger;

class StreetPlugin
{
    public function afterMerge(\Magento\Checkout\Block\Checkout\AttributeMerger $subject, $result)
    {
        if (array_key_exists('street', $result)) {
            $result['street']['children'][0]['placeholder'] = __('Flat No/House No/Building No');
            $result['street']['children'][1]['placeholder'] = __('Street Name/Landmark');
            $result['street']['children'][0]['validation'] = ['required-entry' => true, "min_text_len‌​gth" => 1, "max_text_length" => 50];
            $result['street']['children'][1]['validation'] = ['required-entry' => false, "min_text_len‌​gth" => 1, "max_text_length" => 50];
        }
        return $result;
    }
}