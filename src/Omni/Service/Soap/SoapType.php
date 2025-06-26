<?php
declare(strict_types=1);

namespace Ls\Omni\Service\Soap;

use MyCLabs\Enum\Enum;

/**
 * @method static SoapType RESTRICTION()
 * @method static SoapType ARRAY_OF()
 * @method static SoapType ENTITY()
 */
class SoapType extends Enum
{
    const RESTRICTION = 'Enum';
    const ARRAY_OF = 'ArrayOf';
    const ENTITY = '.';
}
