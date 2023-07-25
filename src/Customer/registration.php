<?php

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Ls_Customer',
    isset($file) ? dirname($file) : __DIR__
);
