<?php

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Ls_Webhooks',
    isset($file) ? dirname($file) : __DIR__
);
