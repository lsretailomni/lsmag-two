<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class ReplEcommDataTranslationLangCodeResponse implements ResponseInterface
{
    /**
     * @property ReplDataTranslationLangCodeResponse
     * $ReplEcommDataTranslationLangCodeResult
     */
    protected $ReplEcommDataTranslationLangCodeResult = null;

    /**
     * @param ReplDataTranslationLangCodeResponse
     * $ReplEcommDataTranslationLangCodeResult
     * @return $this
     */
    public function setReplEcommDataTranslationLangCodeResult($ReplEcommDataTranslationLangCodeResult)
    {
        $this->ReplEcommDataTranslationLangCodeResult = $ReplEcommDataTranslationLangCodeResult;
        return $this;
    }

    /**
     * @return ReplDataTranslationLangCodeResponse
     */
    public function getReplEcommDataTranslationLangCodeResult()
    {
        return $this->ReplEcommDataTranslationLangCodeResult;
    }

    /**
     * @return ReplDataTranslationLangCodeResponse
     */
    public function getResult()
    {
        return $this->ReplEcommDataTranslationLangCodeResult;
    }
}

