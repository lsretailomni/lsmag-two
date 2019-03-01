<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\Ecommerce\Entity\Enum\HierarchyLeafType;
use Ls\Omni\Exception\InvalidEnumException;

class HierarchyLeaf extends HierarchyPoint
{

    /**
     * @property HierarchyLeafType $Type
     */
    protected $Type = null;

    /**
     * @param HierarchyLeafType|string $Type
     * @return $this
     * @throws InvalidEnumException
     */
    public function setType($Type)
    {
        if ( ! $Type instanceof HierarchyLeafType ) {
            if ( HierarchyLeafType::isValid( $Type ) ) 
                $Type = new HierarchyLeafType( $Type );
            elseif ( HierarchyLeafType::isValidKey( $Type ) ) 
                $Type = new HierarchyLeafType( constant( "HierarchyLeafType::$Type" ) );
            elseif ( ! $Type instanceof HierarchyLeafType )
                throw new InvalidEnumException();
        }
        $this->Type = $Type->getValue();
        
        return $this;
    }

    /**
     * @return HierarchyLeafType
     */
    public function getType()
    {
        return $this->Type;
    }


}

