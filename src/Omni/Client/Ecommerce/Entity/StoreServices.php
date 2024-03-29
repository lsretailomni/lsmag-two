<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\Ecommerce\Entity\Enum\StoreServiceType;
use Ls\Omni\Exception\InvalidEnumException;

class StoreServices
{
    /**
     * @property string $Description
     */
    protected $Description = null;

    /**
     * @property string $StoreId
     */
    protected $StoreId = null;

    /**
     * @property StoreServiceType $StoreServiceType
     */
    protected $StoreServiceType = null;

    /**
     * @param string $Description
     * @return $this
     */
    public function setDescription($Description)
    {
        $this->Description = $Description;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->Description;
    }

    /**
     * @param string $StoreId
     * @return $this
     */
    public function setStoreId($StoreId)
    {
        $this->StoreId = $StoreId;
        return $this;
    }

    /**
     * @return string
     */
    public function getStoreId()
    {
        return $this->StoreId;
    }

    /**
     * @param StoreServiceType|string $StoreServiceType
     * @return $this
     * @throws InvalidEnumException
     */
    public function setStoreServiceType($StoreServiceType)
    {
        if ( ! $StoreServiceType instanceof StoreServiceType ) {
            if ( StoreServiceType::isValid( $StoreServiceType ) )
                $StoreServiceType = new StoreServiceType( $StoreServiceType );
            elseif ( StoreServiceType::isValidKey( $StoreServiceType ) )
                $StoreServiceType = new StoreServiceType( constant( "StoreServiceType::$StoreServiceType" ) );
            elseif ( ! $StoreServiceType instanceof StoreServiceType )
                throw new InvalidEnumException();
        }
        $this->StoreServiceType = $StoreServiceType->getValue();

        return $this;
    }

    /**
     * @return StoreServiceType
     */
    public function getStoreServiceType()
    {
        return $this->StoreServiceType;
    }
}

