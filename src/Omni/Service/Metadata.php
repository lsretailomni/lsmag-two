<?php

namespace Ls\Omni\Service;

use DOMDocument;
use DOMElement;
use DOMXPath;
use \Ls\Omni\Service\Soap\Client;
use \Ls\Omni\Service\Soap\ComplexType;
use \Ls\Omni\Service\Soap\ComplexTypeDefinition;
use \Ls\Omni\Service\Soap\Element;
use \Ls\Omni\Service\Soap\Entity;
use \Ls\Omni\Service\Soap\Operation;
use \Ls\Omni\Service\Soap\ReplicationOperation;
use \Ls\Omni\Service\Soap\Restriction;
use \Ls\Omni\Service\Soap\RestrictionDefinition;
use \Ls\Omni\Service\Soap\SoapType;

class Metadata
{
    public const ARRAY_REGEX = '/ArrayOf/';

    /** @var Client */
    public $client;
    /** @var bool */
    public $withReplication;
    /** @var Operation[] */
    public $operations = [];
    /** @var ReplicationOperation[] */
    public $replications = [];
    /** @var Element[] */
    public $elements = [];
    /** @var Element[] */
    public $entities = [];
    /** @var ComplexType[] */
    public $types = [];
    /** @var Restriction[] */
    public $restrictions = [];
    /**
     * @var array
     */
    public $baseClasses = [];
    /** @var DOMDocument */
    public $wsdl;
    /**
     * @var DOMXPath
     */
    public $xpath;

    private $typeBlacklist = [
        'anyType',
        'anyURI',
        'base64Binary',
        'boolean',
        'byte',
        'dateTime',
        'decimal',
        'double',
        'float',
        'int',
        'long',
        'QName',
        'short',
        'string',
        'unsignedByte',
        'unsignedInt',
        'unsignedLong',
        'unsignedShort',
        'char',
        'duration',
        'guid'
    ];

    /**
     * @param Client $client
     * @param bool $withReplication
     */
    public function __construct(Client $client, $withReplication = false)
    {
        $this->client          = $client;
        $this->wsdl            = $this->client->getWsdlXml();
        $this->xpath           = new DOMXPath($this->wsdl);
        $this->withReplication = $withReplication;
        $this->build();
    }

    /**
     * Parse xml and gather entities and operations
     *
     * @return void
     */
    public function build()
    {
        $schemas = $this->wsdl->getElementsByTagName('schema');
        /** @var DOMElement $schema */
        foreach ($schemas as $schema) {
            $this->processOmniSchema($schema);
        }
        $this->processOmniOperations();
        $this->processEntities();
    }

    /**
     * Process all the dom schemas
     *
     * @param DOMElement $schema
     */
    public function processOmniSchema(DOMElement $schema)
    {
        // @codingStandardsIgnoreStart
        $simpleTypes = $this->xpath->query('//*[local-name()=\'schema\']/*[local-name()=\'simpleType\']', $schema);
        $complexTypes = $this->xpath->query('//*[local-name()=\'schema\']/*[local-name()=\'complexType\']', $schema);
        $elements = $this->xpath->query('//*[local-name()=\'schema\']/*[local-name()=\'element\']', $schema);
        // @codingStandardsIgnoreEnd

        // FIRST WE TRAVERSE SIMPLE TYPES
        // THIS TYPE IS REPRESENTING ENUMERATED VALUES MEANT TO BE USED AS string CONSTANTS WITHIN THE API
        for ($i = 0; $i < $simpleTypes->length; $i++) {
            $simpleType = $simpleTypes->item($i);
            $this->parseSimpleType($simpleType);
        }

        for ($i = 0; $i < $complexTypes->length; $i++) {
            $complexType = $complexTypes->item($i);
            $this->parseComplexType($complexType);
        }

        for ($i = 0; $i < $elements->length; $i++) {
            $element = $elements->item($i);
            $this->parseElement($element);
        }
    }

    /**
     * Parse simple type
     *
     * @param DOMElement $simpleType
     *
     * @return Restriction
     */
    protected function parseSimpleType(DOMElement $simpleType)
    {
        $name       = $simpleType->getAttribute('name');
        $definition = [];

        // TRAVERSE TO THE RESTRICTION DEFINITION
        // IF list IS THE FIRST CHILD THEN THE enumeration LIVES A TAG BELOW (simpleType) OF list
        $parentNode = $simpleType->firstChild;
        $isList     = $parentNode->localName == 'list';
        if ($isList) {
            $parentNode = $parentNode->firstChild->firstChild;
        }
        $base = $parentNode->getAttribute('base');

        foreach ($parentNode->childNodes as $restrictionDetail) {
            /** @var DOMElement $restrictionDetail */
            $detailName    = $restrictionDetail->localName;
            $detailValue   = $restrictionDetail->getAttribute('value');
            $detailMapping = $restrictionDetail->nodeValue;
            // @codingStandardsIgnoreLine
            $definition[] = new RestrictionDefinition($detailName, $detailValue, $detailMapping);
        }

        // ASSIGN THE VALUE OF THE enumeration USING THE SERIALIZED VALUE
        // @codingStandardsIgnoreLine
        $this->restrictions[$name] = new Restriction($name, $definition, $base);

        return $this->restrictions[$name];
    }

    /**
     * Parse complex type
     *
     * @param DOMElement $complexType
     * @param string $complexName
     *
     * @return ComplexType
     */
    public function parseComplexType(DOMElement $complexType, $complexName = null)
    {
        /** @var DOMElement $parentNode */
        $parentNode = $complexType->firstChild;
        $sequence = null;

        if ($parentNode->localName == 'sequence') {
            $sequence                          = $this->parseSequence($parentNode, $complexType, $complexName);
            if (isset($this->types[$sequence->getName()])) {
                $before = $this->types[$sequence->getName()]->getDefinition();
                $now = $sequence->getDefinition();
                $merged = array_merge($before, $now);
                $sequence->setDefinition($merged);
            }
            $this->types[$sequence->getName()] = $sequence;

            $this->elements[$sequence->getName()] = new Element($sequence->getName(), $sequence->getName());
        } elseif ($parentNode->localName == 'complexContent') {
            $extension = $parentNode->firstChild;
            $baseClass = explode(":", $extension->getAttribute('base'));
            $sequence = $this->parseSequence(
                $extension->firstChild,
                $complexType,
                $complexName,
                $baseClass[1]
            );
            $this->baseClasses[$sequence->getName()] = $baseClass[1];
            $this->types[$sequence->getName()]       = $sequence;
        }

        ksort($this->types);

        return $sequence;
    }

    /**
     * Parse sequence
     *
     * @param DOMElement $sequence
     * @param DOMElement $complexType
     * @param null $complexName
     * @param string $baseClass
     * @return ComplexType
     */
    public function parseSequence(DOMElement $sequence, DOMElement $complexType, $complexName = null, $baseClass = '')
    {
        $complexDefinition = [];
        $entityName        = $complexName;
        if ($complexName == null) {
            $entityName = $complexType->getAttribute('name');
        }

        foreach ($sequence->childNodes as $element) {
            /** @var DOMElement $element */
            $name      = $element->getAttribute('name');
            $type      = $this->stripType($element->getAttribute('type'));
            $minOccurs = $element->getAttribute('minOccurs');
            // @codingStandardsIgnoreLine
            $complexDefinition[$name] = new ComplexTypeDefinition($name, $type, $minOccurs);
        }

        $isArray = preg_match(Metadata::ARRAY_REGEX, $entityName);
        // @codingStandardsIgnoreLine
        return new ComplexType(
            $entityName,
            $isArray ? SoapType::ARRAY_OF() : SoapType::ENTITY(),
            $complexDefinition,
            $baseClass
        );
    }

    /**
     * Strip types
     *
     * @param string $raw
     * @return string
     */
    private function stripType($raw)
    {
        $parts = explode(':', $raw);
        return array_pop($parts);
    }

    /**
     * Parse element
     *
     * @param DOMElement $element
     */
    public function parseElement(DOMElement $element)
    {
        $name = $element->getAttribute('name');
        // MOST OF THE ELEMENTS ARE SINGLE NODES THAT ARE DEFINED BY THEIR ATTRIBUTES
        if (!$element->hasChildNodes()) {
            $type = $this->stripType($element->getAttribute('type'));
            if (array_search($type, $this->typeBlacklist) === false) {
                // @codingStandardsIgnoreLine
                $this->elements[$name] = new Element($name, $type);
            }
        } elseif (!array_key_exists($name, $this->elements)) {
            $complexType = $this->parseComplexType($element->firstChild, $name);
            // @codingStandardsIgnoreLine
            $this->elements[$complexType->getName()] = new Element($name, $name);
        }

        ksort($this->elements);
    }

    /**
     * THIS METHOD IS CALLED AFTER ALL THE DATA TYPES INFORMATION WAS ACQUIRED
     *
     * WE USE THE PHP NATIVE'S SOAP CLIENT CAPABILITIES TO DISCOVER THE OPERATIONS ALONGSIDE THEIR CONTRACTS
     */
    public function processOmniOperations()
    {
        $regexOperation = '/^(?\'response\'.+)\s(?\'operation\'.+)\((?\'request\'.+)\s.*$/';
        $operations     = $this->client->getSoapClient()->__getFunctions();

        foreach ($operations as $operation) {
            preg_match($regexOperation, $operation, $match);

            $name     = $match['operation'];
            $request  = $match['request'];
            $response = $match['response'];

            $this->elements[$request]->setRequest(true);
            $this->elements[$response]->setResponse(true);
            // @codingStandardsIgnoreLine
            $operationInstance = new Operation(
                $name,
                $this->elements[$request],
                $this->elements[$response]
            );
            $this->operations[$name] = $operationInstance;

            if ($this->withReplication && strpos($name, 'ReplEcomm') !== false) {
                $this->processReplicationOperation($operationInstance);
            }
        }
    }

    /**
     * Process replication operation
     *
     * @param Operation $operation
     */
    private function processReplicationOperation(Operation $operation)
    {
        // @codingStandardsIgnoreStart
        $replicationOperation = new ReplicationOperation(
            $operation->getName(),
            $operation->getRequest(),
            $operation->getResponse()
        );
        // @codingStandardsIgnoreEnd
        $this->replications[$operation->getName()] = $replicationOperation;
    }

    /**
     * Process entities
     *
     * @return void
     */
    public function processEntities()
    {
        $types = $this->client->getSoapClient()->__getTypes();
        foreach ($types as $soapType) {
            $properties = [];

            $lines = explode("\n", $soapType);
            if (!preg_match('/struct (.*) {/', $lines[0], $matches)) {
                continue;
            }
            $entityName = $matches[1];

            foreach (array_slice($lines, 1) as $line) {
                if ($line == '}') {
                    continue;
                }
                preg_match('/\s* (.*) (.*);/', $line, $matches);
                $propertyType = $matches[1];
                $propertyName = $matches[2];

                if (array_key_exists($propertyType, $this->types)) {
                    $propertyType = $this->types[$propertyType];
                } elseif (array_key_exists($propertyType, $this->restrictions)) {
                    $propertyType = $this->restrictions[$propertyType];
                }
                $properties[$propertyName] = $propertyType;
            }
            $merged = $properties;
            if (isset($this->entities[$entityName])) {
                $before = $this->entities[$entityName]->getDefinition();
                $now = $properties;
                $merged = array_merge($before, $now);
            }
            // @codingStandardsIgnoreLine
            $this->entities[$entityName] = new Entity(
                $entityName,
                $this->elements[$entityName],
                $merged
            );
        }
    }

    /**
     * Get replication operation by name
     *
     * @param $operationName
     * @return ReplicationOperation|null
     */
    public function getReplicationOperationByName($operationName)
    {
        return $this->replications[$operationName] ?? null;
    }

    /**
     * Get all replication operations.
     *
     * @return ReplicationOperation[]
     */
    public function getReplicationOperations()
    {
        return $this->replications;
    }

    /**
     * Set the list of replication operations.
     *
     * @param ReplicationOperation[] $replication
     */
    public function setReplicationOperations($replication)
    {
        $this->replications = $replication;
    }

    /**
     * Get the SOAP client instance.
     *
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Get all parsed complex types.
     *
     * @return ComplexType[]
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * Get all parsed operations.
     *
     * @return Operation[]
     */
    public function getOperations()
    {
        return $this->operations;
    }

    /**
     * Get all parsed elements.
     *
     * @return Element[]
     */
    public function getElements()
    {
        return $this->elements;
    }

    /**
     * Get all parsed restrictions (simple types/enums).
     *
     * @return Restriction[]
     */
    public function getRestrictions()
    {
        return $this->restrictions;
    }

    /**
     * Get all base class mappings for extended complex types.
     *
     * @return string[]
     */
    public function getBaseClasses()
    {
        return $this->baseClasses;
    }

    /**
     * Get all parsed entities.
     *
     * @return Element[]
     */
    public function getEntities()
    {
        return $this->entities;
    }
}
