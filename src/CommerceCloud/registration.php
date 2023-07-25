<?php

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Ls_CommerceCloud',
    isset($file) ? $file->dirname($file) : __DIR__
);
