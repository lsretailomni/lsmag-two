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

    public function setDel($Del)
    {
        $this->Del = $Del;
        return $this;
    }

    public function getDel()
    {
        return $this->Del;
    }

    public function setFrameworkCode($FrameworkCode)
    {
        $this->FrameworkCode = $FrameworkCode;
        return $this;
    }

    public function getFrameworkCode()
    {
        return $this->FrameworkCode;
    }

    public function setItemId($ItemId)
    {
        $this->ItemId = $ItemId;
        return $this;
    }

    public function getItemId()
    {
        return $this->ItemId;
    }

    public function setVarDim1($VarDim1)
    {
        $this->VarDim1 = $VarDim1;
        return $this;
    }

    public function getVarDim1()
    {
        return $this->VarDim1;
    }

    public function setVarDim2($VarDim2)
    {
        $this->VarDim2 = $VarDim2;
        return $this;
    }

    public function getVarDim2()
    {
        return $this->VarDim2;
    }

    public function setVarDim3($VarDim3)
    {
        $this->VarDim3 = $VarDim3;
        return $this;
    }

    public function getVarDim3()
    {
        return $this->VarDim3;
    }

    public function setVarDim4($VarDim4)
    {
        $this->VarDim4 = $VarDim4;
        return $this;
    }

    public function getVarDim4()
    {
        return $this->VarDim4;
    }

    public function setVarDim5($VarDim5)
    {
        $this->VarDim5 = $VarDim5;
        return $this;
    }

    public function getVarDim5()
    {
        return $this->VarDim5;
    }

    public function setVarDim6($VarDim6)
    {
        $this->VarDim6 = $VarDim6;
        return $this;
    }

    public function getVarDim6()
    {
        return $this->VarDim6;
    }

    public function setVariantId($VariantId)
    {
        $this->VariantId = $VariantId;
        return $this;
    }

    public function getVariantId()
    {
        return $this->VariantId;
    }


}

