<?php
namespace Ls\Omni\Service;

use MyCLabs\Enum\Enum;

/**
 * @method static ServiceType ECOMMERCE()
 * @method static ServiceType LOYALTY()
 * @method static ServiceType GENERAL()
 */
class ServiceType extends Enum
{
    const ECOMMERCE = 'ecommerce';
    const LOYALTY = 'loyalty';
    const GENERAL = 'general';
}
