<?php
namespace Ls\Omni\Service\Soap;

class ComplexTypeDefinition
{
    /** @var string */
    private $name;
    /** @var string */
    private $data_type;
    /** @var integer */
    private $min_occurs;

    /**
     * ComplexDefinition constructor.
     *
     * @param string $name
     * @param string $data_type
     * @param int    $min_occurs
     */
    public function __construct ( $name, $data_type, $min_occurs ) {
        $this->name = $name;
        $this->data_type = $data_type;
        $this->min_occurs = $min_occurs;
    }

    /**
     * @return string
     */
    public function getName () {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName ( $name ) {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getDataType () {
        return $this->data_type;
    }

    /**
     * @param string $data_type
     */
    public function setDataType ( $data_type ) {
        $this->data_type = $data_type;
    }

    /**
     * @return int
     */
    public function getMinOccurs () {
        return $this->min_occurs;
    }

    /**
     * @param int $min_occurs
     */
    public function setMinOccurs ( $min_occurs ) {
        $this->min_occurs = $min_occurs;
    }


}
