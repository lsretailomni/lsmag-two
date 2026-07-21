<?php

namespace Ls\Customer\Model;

use Magento\Framework\View\Design\ThemeInterface;
use Magento\Framework\View\DesignInterface;

/**
 * Class HyvaThemeResolver
 *
 * Detects whether the currently active frontend theme is a Hyvä theme by walking the
 * theme inheritance chain and looking for a Hyvä base theme. Implemented without any
 * dependency on the Hyva_Theme module so the Ls_Customer module keeps working on
 * Luma-only installations.
 */
class HyvaThemeResolver
{
    /**
     * @var DesignInterface
     */
    private $design;

    /**
     * @param DesignInterface $design
     */
    public function __construct(DesignInterface $design)
    {
        $this->design = $design;
    }

    /**
     * Returns true when the active theme (or any of its parents) is a Hyvä theme.
     *
     * @return bool
     */
    public function isHyva()
    {
        $theme = $this->design->getDesignTheme();

        while ($theme instanceof ThemeInterface) {
            if (stripos((string) $theme->getCode(), 'Hyva/') === 0) {
                return true;
            }
            $theme = $theme->getParentTheme();
        }

        return false;
    }
}
