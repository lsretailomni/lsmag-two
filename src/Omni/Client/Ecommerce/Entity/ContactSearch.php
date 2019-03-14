<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\Ecommerce\Entity\Enum\ContactSearchType;
use Ls\Omni\Exception\InvalidEnumException;
use Ls\Omni\Client\RequestInterface;

class ContactSearch implements RequestInterface
{

    /**
     * @property ContactSearchType $searchType
     */
    protected $searchType = null;

    /**
     * @property string $search
     */
    protected $search = null;

    /**
     * @property int $maxNumberOfRowsReturned
     */
    protected $maxNumberOfRowsReturned = null;

    /**
     * @param ContactSearchType|string $searchType
     * @return $this
     * @throws InvalidEnumException
     */
    public function setSearchType($searchType)
    {
        if ( ! $searchType instanceof ContactSearchType ) {
            if ( ContactSearchType::isValid( $searchType ) ) 
                $searchType = new ContactSearchType( $searchType );
            elseif ( ContactSearchType::isValidKey( $searchType ) ) 
                $searchType = new ContactSearchType( constant( "ContactSearchType::$searchType" ) );
            elseif ( ! $searchType instanceof ContactSearchType )
                throw new InvalidEnumException();
        }
        $this->searchType = $searchType->getValue();

        return $this;
    }

    /**
     * @return ContactSearchType
     */
    public function getSearchType()
    {
        return $this->searchType;
    }

    /**
     * @param string $search
     * @return $this
     */
    public function setSearch($search)
    {
        $this->search = $search;
        return $this;
    }

    /**
     * @return string
     */
    public function getSearch()
    {
        return $this->search;
    }

    /**
     * @param int $maxNumberOfRowsReturned
     * @return $this
     */
    public function setMaxNumberOfRowsReturned($maxNumberOfRowsReturned)
    {
        $this->maxNumberOfRowsReturned = $maxNumberOfRowsReturned;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxNumberOfRowsReturned()
    {
        return $this->maxNumberOfRowsReturned;
    }


}

