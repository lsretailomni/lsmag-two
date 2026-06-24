<?php

declare(strict_types=1);

namespace Ls\Omni\Observer;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Registers Ls_Omni with the Hyvä Tailwind build.
 *
 * `bin/magento hyva:config:generate` dispatches "hyva_config_generate_before";
 * appending this module's path to the config "extensions" makes `hyva-sources`
 * pick up view/frontend/tailwind/module.css and compile it into the theme's
 * single styles.css.
 *
 * The event is only dispatched when Hyvä is installed, so this observer has no
 * effect on Luma-only installations (and carries no hard Hyvä dependency).
 */
class RegisterModuleForHyvaConfig implements ObserverInterface
{
    public function __construct(
        private ComponentRegistrar $componentRegistrar
    ) {
    }

    public function execute(Observer $observer)
    {
        $config     = $observer->getData('config');
        $extensions = $config->hasData('extensions') ? $config->getData('extensions') : [];

        $path = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, 'Ls_Omni');
        if ($path && str_starts_with($path, BP)) {
            $extensions[] = ['src' => substr($path, strlen(BP) + 1)];
            $config->setData('extensions', $extensions);
        }
    }
}
