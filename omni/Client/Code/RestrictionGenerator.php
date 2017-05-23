<?php
namespace Ls\Omni\Client\Code;

use Ls\Omni\Service\Metadata;
use Ls\Omni\Service\Soap\Restriction;
use MyCLabs\Enum\Enum;
use Zend\Code\Generator\DocBlock\Tag;

class RestrictionGenerator extends AbstractGenerator
{
    /** @var Restriction */
    private $restriction;

    /**
     * @param Restriction $restriction
     * @param Metadata    $metadata
     */
    public function __construct ( Restriction $restriction, Metadata $metadata ) {
        parent::__construct( $metadata );
        $this->restriction = $restriction;
    }

    /**
     * @var array
     */
    protected $equivalences = [
        'decimal' => 'float',
        'long' => 'int',
        'dateTime' => 'string',
        'NotificationStatus' => 'string',
    ];

    /**
     * @param string $data_type
     *
     * @return string
     */
    protected function normalizeDataType ( $data_type ) {
        return array_key_exists( $data_type, $this->equivalences ) ? $this->equivalences[ $data_type ] : $data_type;
    }

    function generate () {

        $service_folder = ucfirst( $this->getServiceType()->getValue() );
        $base_namespace = self::fqn( 'Ls', 'Omni', 'Client', $service_folder );
        $entity_namespace = self::fqn( $base_namespace, 'Entity', 'Enum' );
        $enum_class = Enum::class;

        $this->class->setNamespaceName( $entity_namespace );
        $this->class->addUse( Enum::class );
        $this->class->setName( $this->restriction->getName() );
        $this->class->setExtendedClass( Enum::class );

        foreach ( $this->restriction->getDefinition() as $definition ) {
            $this->class->addConstant( $definition->getValue(), $definition->getValue() );
        }

        $this->file->setClass( $this->class );
        $content = $this->file->generate();

        $content = str_replace( "extends {$enum_class}", 'implements Enum', $content );

        return $content;
    }
}
