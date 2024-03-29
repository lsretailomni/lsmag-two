<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\Ecommerce\Entity\Enum\VSTimeScheduleType;
use Ls\Omni\Exception\InvalidEnumException;

class VSTimeSchedule
{
    /**
     * @property ArrayOfVSTimeScheduleLine $Lines
     */
    protected $Lines = null;

    /**
     * @property string $Description
     */
    protected $Description = null;

    /**
     * @property string $Id
     */
    protected $Id = null;

    /**
     * @property VSTimeScheduleType $Type
     */
    protected $Type = null;

    /**
     * @param ArrayOfVSTimeScheduleLine $Lines
     * @return $this
     */
    public function setLines($Lines)
    {
        $this->Lines = $Lines;
        return $this;
    }

    /**
     * @return ArrayOfVSTimeScheduleLine
     */
    public function getLines()
    {
        return $this->Lines;
    }

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
     * @param string $Id
     * @return $this
     */
    public function setId($Id)
    {
        $this->Id = $Id;
        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->Id;
    }

    /**
     * @param VSTimeScheduleType|string $Type
     * @return $this
     * @throws InvalidEnumException
     */
    public function setType($Type)
    {
        if ( ! $Type instanceof VSTimeScheduleType ) {
            if ( VSTimeScheduleType::isValid( $Type ) )
                $Type = new VSTimeScheduleType( $Type );
            elseif ( VSTimeScheduleType::isValidKey( $Type ) )
                $Type = new VSTimeScheduleType( constant( "VSTimeScheduleType::$Type" ) );
            elseif ( ! $Type instanceof VSTimeScheduleType )
                throw new InvalidEnumException();
        }
        $this->Type = $Type->getValue();

        return $this;
    }

    /**
     * @return VSTimeScheduleType
     */
    public function getType()
    {
        return $this->Type;
    }
}

