<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Replication\Api\Data;

interface ReplHierarchyInterface
{

    /**
     * @param string $Description
     * @return $this
     */
    public function setDescription($Description);

    /**
     * @return string
     */
    public function getDescription();

    /**
     * @param string $nav_id
     * @return $this
     */
    public function setNavId($nav_id);

    /**
     * @return string
     */
    public function getNavId();

    /**
     * @param boolean $IsDeleted
     * @return $this
     */
    public function setIsDeleted($IsDeleted);

    /**
     * @return boolean
     */
    public function getIsDeleted();

    /**
     * @param HierarchyType $Type
     * @return $this
     */
    public function setType($Type);

    /**
     * @return HierarchyType
     */
    public function getType();

    /**
     * @param string $scope
     * @return $this
     */
    public function setScope($scope);

    /**
     * @return string
     */
    public function getScope();

    /**
     * @param int $scope_id
     * @return $this
     */
    public function setScopeId($scope_id);

    /**
     * @return int
     */
    public function getScopeId();

    /**
     * @param boolean $processed
     * @return $this
     */
    public function setProcessed($processed);

    /**
     * @return boolean
     */
    public function getProcessed();

    /**
     * @param boolean $is_updated
     * @return $this
     */
    public function setIsUpdated($is_updated);

    /**
     * @return boolean
     */
    public function getIsUpdated();

    /**
     * @param boolean $is_failed
     * @return $this
     */
    public function setIsFailed($is_failed);

    /**
     * @return boolean
     */
    public function getIsFailed();

    /**
     * @param string $created_at
     * @return $this
     */
    public function setCreatedAt($created_at);

    /**
     * @return string
     */
    public function getCreatedAt();

    /**
     * @param string $updated_at
     * @return $this
     */
    public function setUpdatedAt($updated_at);

    /**
     * @return string
     */
    public function getUpdatedAt();

    /**
     * @param string $checksum
     * @return $this
     */
    public function setChecksum($checksum);

    /**
     * @return string
     */
    public function getChecksum();

    /**
     * @param string $processed_at
     * @return $this
     */
    public function setProcessedAt($processed_at);

    /**
     * @return string
     */
    public function getProcessedAt();


}

