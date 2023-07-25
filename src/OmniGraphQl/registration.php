<?php

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Ls_OmniGraphQl',
    isset($file) ? dirname($file) : __DIR__
);
