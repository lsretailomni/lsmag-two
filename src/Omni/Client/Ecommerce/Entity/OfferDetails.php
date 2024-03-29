<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

class OfferDetails
{
    /**
     * @property string $Description
     */
    protected $Description = null;

    /**
     * @property ImageView $Image
     */
    protected $Image = null;

    /**
     * @property string $LineNumber
     */
    protected $LineNumber = null;

    /**
     * @property string $OfferId
     */
    protected $OfferId = null;

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
     * @param ImageView $Image
     * @return $this
     */
    public function setImage($Image)
    {
        $this->Image = $Image;
        return $this;
    }

    /**
     * @return ImageView
     */
    public function getImage()
    {
        return $this->Image;
    }

    /**
     * @param string $LineNumber
     * @return $this
     */
    public function setLineNumber($LineNumber)
    {
        $this->LineNumber = $LineNumber;
        return $this;
    }

    /**
     * @return string
     */
    public function getLineNumber()
    {
        return $this->LineNumber;
    }

    /**
     * @param string $OfferId
     * @return $this
     */
    public function setOfferId($OfferId)
    {
        $this->OfferId = $OfferId;
        return $this;
    }

    /**
     * @return string
     */
    public function getOfferId()
    {
        return $this->OfferId;
    }
}

