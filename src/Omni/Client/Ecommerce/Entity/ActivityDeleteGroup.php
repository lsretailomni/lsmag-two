<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\RequestInterface;

class ActivityDeleteGroup implements RequestInterface
{
    /**
     * @property string $groupNo
     */
    protected $groupNo = null;

    /**
     * @property int $lineNo
     */
    protected $lineNo = null;

    /**
     * @param string $groupNo
     * @return $this
     */
    public function setGroupNo($groupNo)
    {
        $this->groupNo = $groupNo;
        return $this;
    }

    /**
     * @return string
     */
    public function getGroupNo()
    {
        return $this->groupNo;
    }

    /**
     * @param int $lineNo
     * @return $this
     */
    public function setLineNo($lineNo)
    {
        $this->lineNo = $lineNo;
        return $this;
    }

    /**
     * @return int
     */
    public function getLineNo()
    {
        return $this->lineNo;
    }
}

