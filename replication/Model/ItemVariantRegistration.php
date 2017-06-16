<?php

namespace Ls\Replication\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Ls\Replication\Api\Data\ItemVariantRegistrationInterface;

class ItemVariantRegistration extends AbstractModel implements ItemVariantRegistrationInterface, IdentityInterface
{

    const CACHE_TAG = 'lsr_replication_item_variant_registration';

    protected $_cacheTag = 'lsr_replication_item_variant_registration';

    protected $_eventPrefix = 'lsr_replication_item_variant_registration';

    protected $Del = null;

    protected $FrameworkCode = null;

    protected $ItemId = null;

    protected $VarDim1 = null;

    protected $VarDim2 = null;

    protected $VarDim3 = null;

    protected $VarDim4 = null;

    protected $VarDim5 = null;

    protected $VarDim6 = null;

    protected $VariantId = null;

    public function _construct()
    {
        $this->_init( 'Ls\Replication\Model\ResourceModel\ItemVariantRegistration' );
    }

    public function getIdentities()
    {
        return [ self::CACHE_TAG . '_' . $this->getId() ];
    }

    /**
     * @return $this
     */
    public function setDel($Del)
    {
        $this->setData( 'Del', $Del );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getDel()
    {
        return $this->Del;
    }

    /**
     * @return $this
     */
    public function setFrameworkCode($FrameworkCode)
    {
        $this->setData( 'FrameworkCode', $FrameworkCode );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getFrameworkCode()
    {
        return $this->FrameworkCode;
    }

    /**
     * @return $this
     */
    public function setItemId($ItemId)
    {
        $this->setData( 'ItemId', $ItemId );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getItemId()
    {
        return $this->ItemId;
    }

    /**
     * @return $this
     */
    public function setVarDim1($VarDim1)
    {
        $this->setData( 'VarDim1', $VarDim1 );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getVarDim1()
    {
        return $this->VarDim1;
    }

    /**
     * @return $this
     */
    public function setVarDim2($VarDim2)
    {
        $this->setData( 'VarDim2', $VarDim2 );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getVarDim2()
    {
        return $this->VarDim2;
    }

    /**
     * @return $this
     */
    public function setVarDim3($VarDim3)
    {
        $this->setData( 'VarDim3', $VarDim3 );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getVarDim3()
    {
        return $this->VarDim3;
    }

    /**
     * @return $this
     */
    public function setVarDim4($VarDim4)
    {
        $this->setData( 'VarDim4', $VarDim4 );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getVarDim4()
    {
        return $this->VarDim4;
    }

    /**
     * @return $this
     */
    public function setVarDim5($VarDim5)
    {
        $this->setData( 'VarDim5', $VarDim5 );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getVarDim5()
    {
        return $this->VarDim5;
    }

    /**
     * @return $this
     */
    public function setVarDim6($VarDim6)
    {
        $this->setData( 'VarDim6', $VarDim6 );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getVarDim6()
    {
        return $this->VarDim6;
    }

    /**
     * @return $this
     */
    public function setVariantId($VariantId)
    {
        $this->setData( 'VariantId', $VariantId );
        $this->setDataChanges( TRUE );
        return $this;
    }

    public function getVariantId()
    {
        return $this->VariantId;
    }


}

