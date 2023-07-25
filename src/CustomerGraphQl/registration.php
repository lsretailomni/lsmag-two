<?php

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Ls_CustomerGraphQl',
    isset($file) ? $file->dirname($file) : __DIR__
);
