<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class ReplEcommImagesResponse implements ResponseInterface
{
    /**
     * @property ReplImageResponse $ReplEcommImagesResult
     */
    protected $ReplEcommImagesResult = null;

    /**
     * @param ReplImageResponse $ReplEcommImagesResult
     * @return $this
     */
    public function setReplEcommImagesResult($ReplEcommImagesResult)
    {
        $this->ReplEcommImagesResult = $ReplEcommImagesResult;
        return $this;
    }

    /**
     * @return ReplImageResponse
     */
    public function getReplEcommImagesResult()
    {
        return $this->ReplEcommImagesResult;
    }

    /**
     * @return ReplImageResponse
     */
    public function getResult()
    {
        return $this->ReplEcommImagesResult;
    }
}

