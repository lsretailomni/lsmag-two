<?php

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Ls_Omni',
    isset($file) ? $file->dirname($file) : __DIR__
);
