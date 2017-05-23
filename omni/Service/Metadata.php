<?php
namespace Ls\Omni\Service;

use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;
use Ls\Omni\Service\Soap\Client;
use Ls\Omni\Service\Soap\ComplexType;
use Ls\Omni\Service\Soap\ComplexTypeDefinition;
use Ls\Omni\Service\Soap\Element;
use Ls\Omni\Service\Soap\Entity;
use Ls\Omni\Service\Soap\Operation;
use Ls\Omni\Service\Soap\Restriction;
use Ls\Omni\Service\Soap\RestrictionDefinition;
use Ls\Omni\Service\Soap\SoapType;

class Metadata
{
    const ARRAY_REGEX = '/ArrayOf/';

    /** @var Client */
    protected $client;
    /** @var Operation[] */
    protected $operations = [ ];
    /** @var Element[] */
    protected $elements = [ ];
    /** @var Element[] */
    protected $entities = [ ];
    /** @var ComplexType[] */
    protected $types = [ ];
    /** @var Restriction[] */
    protected $restrictions = [ ];
    /** @var DOMDocument */
    protected $wsdl;
    private $type_blacklist = [ 'anyType', 'anyURI', 'base64Binary', 'boolean', 'byte', 'dateTime', 'decimal',
                                'double', 'float', 'int', 'long', 'QName', 'short', 'string', 'unsignedByte',
                                'unsignedInt', 'unsignedLong', 'unsignedShort', 'char', 'duration', 'guid' ];

    /**
     * @param Client $client
     */
    public function __construct ( Client $client ) {

        $this->client = $client;
        $this->wsdl = $this->client->getWsdlXml();
        $this->xpath = new DOMXPath( $this->wsdl );
        $this->build();
    }

    /**
     * MAIN HOOK TO PROCESS THE WSDL
     *  THE OBJECTIVE IS TO GRAB THE DETAILS OF THE DATA TYPES & OPERATIONS
     */
    protected function build () {

        $omni_namespace_regex = '/(lsomni|lsretail)/';

        $schemas = $this->wsdl->getElementsByTagName( 'schema' );
        /** @var DOMElement $schema */
        foreach ( $schemas as $schema ) {
            $namespace = strtolower( $schema->getAttribute( 'targetNamespace' ) );
            if ( preg_match( $omni_namespace_regex, $namespace ) ) {
                $this->processOmniSchema( $schema );
            }
        }
        $this->processOmniOperations();
        $this->processEntities();
    }

    /**
     * @param DOMElement $schema
     */
    protected function processOmniSchema ( DOMElement $schema ) {

        /** @var DOMNodeList $simple_types */
        $simple_types = $this->xpath->query( '//*[local-name()=\'schema\']/*[local-name()=\'simpleType\']', $schema );
        /** @var DOMNodeList $complex_types */
        $complex_types = $this->xpath->query( '//*[local-name()=\'schema\']/*[local-name()=\'complexType\']', $schema );
        /** @var DOMNodeList $elements */
        $elements = $this->xpath->query( '//*[local-name()=\'schema\']/*[local-name()=\'element\']', $schema );

        // FIRST WE TRAVERSE SIMPLE TYPES
        // THIS TYPE IS REPRESENTING ENUMERATED VALUES MEANT TO BE USED AS string CONSTANTS WITHIN THE API
        for ( $i = 0; $i < $simple_types->length; $i++ ) {
            /** @var DOMElement $simple_type */
            $simple_type = $simple_types->item( $i );
            $this->parseSimpleType( $simple_type );
        }

        for ( $i = 0; $i < $complex_types->length; $i++ ) {
            /** @var DOMElement $complex_type */
            $complex_type = $complex_types->item( $i );
            $this->parseComplexType( $complex_type );
        }

        for ( $i = 0; $i < $elements->length; $i++ ) {
            /** @var DOMElement $element */
            $element = $elements->item( $i );
            $this->parseElement( $element );
        }
    }

    /**
     * @param DOMElement $simple_type
     *
     * @return Restriction
     */
    protected function parseSimpleType ( DOMElement $simple_type ) {

        $name = $simple_type->getAttribute( 'name' );
        $definition = [ ];


        // TRAVERSE TO THE RESTRICTION DEFINITION
        // IF list IS THE FIRST CHILD THEN THE enumeration LIVES A TAG BELOW (simpleType) OF list
        $parent_node = $simple_type->firstChild;
        $is_list = $parent_node->localName == 'list';
        if ( $is_list ) {
            $parent_node = $parent_node->firstChild->firstChild;
        }
        $base = $parent_node->getAttribute( 'base' );

        // GOING INSIDE THE restriction TAG
        // <xs:simpleType name="ContactSearchType">
        //     <xs:restriction base="xs:string">
        //       <xs:enumeration value="CardId"/>
        //       <xs:enumeration value="ContactNumber"/>
        //       <xs:enumeration value="PhoneNumber"/>
        //       <xs:enumeration value="Email"/>
        //       <xs:enumeration value="Name"/>
        //     </xs:restriction>
        // </xs:simpleType>
        foreach ( $parent_node->childNodes as $restriction_detail ) {
            /** @var DOMElement $restriction_detail */
            $detail_name = $restriction_detail->localName;
            $detail_value = $restriction_detail->getAttribute( 'value' );
            $detail_mapping = $restriction_detail->nodeValue;

            $definition[] = new RestrictionDefinition( $detail_name, $detail_value, $detail_mapping );
        }

        // ASSIGN THE VALUE OF THE enumeration USING THE SERIALIZED VALUE
        $this->restrictions[ $name ] = new Restriction( $name, $definition, $base );

        return $this->restrictions[ $name ];
    }

    /**
     * @param DOMElement $complex_type
     * @param string     $complex_name
     *
     * @return ComplexType
     */
    protected function parseComplexType ( DOMElement $complex_type, $complex_name = NULL ) {

        // TRAVERSE TO THE TYPE DEFINITION
        /** @var DOMElement $parent_node */
        $parent_node = $complex_type->firstChild;
        $sequence = NULL;

        // MOST COMPLEX TYPES ARE DEFINED BY sequence
        // <xs:complexType name="Environment">
        //     <xs:sequence>
        //       <xs:element minOccurs="0" name="Currency" nillable="true" type="tns:Currency"/>
        //       <xs:element minOccurs="0" name="PasswordPolicy" nillable="true" type="xs:string"/>
        //       <xs:element minOccurs="0" name="Version" nillable="true" type="xs:string"/>
        //     </xs:sequence>
        // </xs:complexType>
        if ( $parent_node->localName == 'sequence' ) {
            $sequence = $this->parseSequence( $parent_node, $complex_type, $complex_name );
            $this->types[ $sequence->getName() ] = $sequence;
        } elseif ( $parent_node->localName == 'complexContent' ) {
            $extension = $parent_node->firstChild;
            $base_type = $this->stripType( $extension->getAttribute( 'base' ) );

            $sequence = $this->parseSequence( $extension->firstChild, $complex_type, $complex_name );
            $sequence->setDefinition( array_merge( $this->types[ $base_type ]->getDefinition(),
                                                   $sequence->getDefinition() ) );

            $this->types[ $sequence->getName() ] = $sequence;
        }

        return $sequence;
    }

    /**
     * @param DOMElement $sequence
     * @param DOMElement $complex_type
     * @param string     $complex_name
     *
     * @return ComplexType
     */
    protected function parseSequence ( DOMElement $sequence, DOMElement $complex_type, $complex_name = NULL ) {
        $complex_definition = [ ];
        $entity_name = $complex_name;
        if ( is_null( $complex_name ) ) {
            $entity_name = $complex_type->getAttribute( 'name' );
        }

        foreach ( $sequence->childNodes as $element ) {
            /** @var DOMElement $element */
            $name = $element->getAttribute( 'name' );
            $type = $this->stripType( $element->getAttribute( 'type' ) );
            $min_occurs = $element->getAttribute( 'minOccurs' );

            $complex_definition [ $name ] = new ComplexTypeDefinition( $name, $type, $min_occurs );
        }
        $is_array = preg_match( Metadata::ARRAY_REGEX, $entity_name );

        return new ComplexType( $entity_name,
                                $is_array ? SoapType::ARRAY_OF() : SoapType::ENTITY(),
                                $complex_definition );

    }

    /**
     * @param string $raw
     *
     * @return string
     */
    private function stripType ( $raw ) {
        $parts = explode( ':', $raw );
        $type = array_pop( $parts );

        return $type;
    }

    /**
     * @param DOMElement $element
     */
    protected function parseElement ( DOMElement $element ) {

        $name = $element->getAttribute( 'name' );
        // MOST OF THE ELEMENTS ARE SINGLE NODES THAT ARE DEFINED BY THEIR ATTRIBUTES
        // <xs:element name="CurrencyRoundingMethod" nillable="true" type="tns:CurrencyRoundingMethod"/>
        if ( !$element->hasChildNodes() ) {
            $type = $this->stripType( $element->getAttribute( 'type' ) );
            if ( array_search( $type, $this->type_blacklist ) === FALSE ) {
                $this->elements[ $name ] = new Element( $name, $type );
            }
        } // OTHERWISE THE DEFINITION IS EMBEDDED AS A complexType
        elseif ( !array_key_exists( $name, $this->elements ) ) {
            $complex_type = $this->parseComplexType( $element->firstChild, $name );
            $this->elements[ $complex_type->getName() ] = new Element( $name, $name );
        }
    }

    /**
     * THIS METHOD IS CALLED AFTER ALL THE DATA TYPES INFORMATION WAS ACQUIRED
     * WE USE THE PHP NATIVE'S SOAP CLIENT CAPABILITIES TO DISCOVER THE OPERATIONS ALONGSIDE THEIR CONTRACTS
     */
    protected function processOmniOperations () {

        $regex_operation = '/^(?\'response\'.+)\s(?\'operation\'.+)\((?\'request\'.+)\s.*$/';
        $operations = $this->client->getSoapClient()->__getFunctions();

        foreach ( $operations as $operation ) {
            preg_match( $regex_operation, $operation, $match );

            $name = $match[ 'operation' ];
            $request = $match[ 'request' ];
            $response = $match[ 'response' ];

            $this->elements[ $request ]->setRequest( TRUE );
            $this->elements[ $response ]->setResponse( TRUE );

            $this->operations[ $match[ 'operation' ] ] = new Operation( $name,
                                                                        $this->elements[ $request ],
                                                                        $this->elements[ $response ] );
        }
    }

    protected function processEntities () {
        $types = $this->client->getSoapClient()->__getTypes();
        foreach ( $types as $soap_type ) {
            $properties = [ ];

            $lines = explode( "\n", $soap_type );
            if ( !preg_match( '/struct (.*) {/', $lines[ 0 ], $matches ) ) {
                continue;
            }
            $entity_name = $matches[ 1 ];

            foreach ( array_slice( $lines, 1 ) as $line ) {
                if ( $line == '}' ) {
                    continue;
                }
                preg_match( '/\s* (.*) (.*);/', $line, $matches );
                $property_type = $matches[ 1 ];
                $property_name = $matches[ 2 ];

                if ( array_key_exists( $property_type, $this->types ) ) {
                    $property_type = $this->types[ $property_type ];
                } elseif ( array_key_exists( $property_type, $this->restrictions ) ) {
                    $property_type = $this->restrictions[ $property_type ];
                }
                $properties[ $property_name ] = $property_type;
            }

            $this->entities[ $entity_name ] = new Entity( $entity_name,
                                                          $this->elements[ $entity_name ],
                                                          $properties );
        }
    }

    /**
     * @return Client
     */
    public function getClient () {
        return $this->client;
    }

    /**
     * @return ComplexType[]
     */
    public function getTypes () {
        return $this->types;
    }

    /**
     * @return Operation[]
     */
    public function getOperations () {
        return $this->operations;
    }

    /**
     * @return Element[]
     */
    public function getElements () {
        return $this->elements;
    }

    /**
     * @return Restriction[]
     */
    public function getRestrictions () {
        return $this->restrictions;
    }

    /**
     * @return Entity[]
     */
    public function getEntities () {
        return $this->entities;
    }

}
