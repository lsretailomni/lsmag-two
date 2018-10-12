<?php

namespace Ls\Core\Code;

use CaseHelper\CaseHelperFactory;
use CaseHelper\CaseHelperInterface;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\DocBlock\Tag;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\GeneratorInterface;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\PropertyGenerator;

abstract class AbstractGenerator implements GeneratorInterface
{
    /** @var FileGenerator */
    protected $file;
    /** @var ClassGenerator */
    protected $class;
    /** @var  CaseHelperInterface */
    protected $case_helper;
    /** @var string */
    private $disclaimer = <<<DISCLAIMER
THIS IS AN AUTOGENERATED FILE
DO NOT MODIFY
DISCLAIMER;

    /**
     * AbstractGenerator constructor.
     * @throws \Exception
     */

    public function __construct()
    {

        $this->file = new FileGenerator();
        $this->file->setDocBlock(DocBlockGenerator::fromArray(['shortdescription' => $this->disclaimer]));
        $this->class = new ClassGenerator();
        $this->case_helper = CaseHelperFactory::make(CaseHelperFactory::INPUT_TYPE_PASCAL_CASE);
        $this->file->setClass($this->class);
    }

    /**
     * @param string ,...
     *
     * @return string
     */
    public static function fqn()
    {
        $parts = func_get_args();

        return join('\\', $parts);
    }

    /**
     * @param string ,...
     *
     * @return string
     */
    public static function path()
    {
        $parts = func_get_args();

        return join(DIRECTORY_SEPARATOR, $parts);
    }

    /**
     * @return FileGenerator
     */
    protected function getFile()
    {
        return $this->file;
    }

    /**
     * @return ClassGenerator
     */
    protected function getClass()
    {
        return $this->class;
    }

    /**
     * @param string $name
     * @param string $type
     * @param array $flags
     * @param array $options
     */
    protected function createProperty($name,
                                      $type = 'mixed',
                                      $flags = [PropertyGenerator::FLAG_PROTECTED],
                                      $options = [])
    {

        $pascal_name = key_exists('pascal_name', $options)
            ? $options ['pascal_name']
            : ucfirst($this->case_helper->toCamelCase($name));
        $variable_name = key_exists('variable_name', $options)
            ? $options ['variable_name']
            : strtolower($this->case_helper->toSnakeCase($name));
        $variable_field = key_exists('variable_field', $options) ? $options ['variable_field'] : $variable_name;

        $set_method = new MethodGenerator();
        $get_method = new MethodGenerator();

        $set_method->setName("set$pascal_name");
        $get_method->setName("get$pascal_name");

        $set_method->setParameter(ParameterGenerator::fromArray(['name' => $variable_name]));

        $get_method->setDocBlock(DocBlockGenerator::fromArray(['tags' => [new Tag\ReturnTag([$type])]]));
        $set_method->setDocBlock(DocBlockGenerator::fromArray(['tags' => [new Tag\ParamTag($variable_name, $type),
            new Tag\ReturnTag(['$this'])]]));
        if (key_exists('abstract', $options)) {
            $get_method->setAbstract(TRUE);
            $set_method->setAbstract(TRUE);
        }

        if (!key_exists('abstract', $options) && !key_exists('interface', $options)) {

            if (key_exists('model', $options)) {

// set & get methods for a magento model
                $set_method->setBody(<<<CODE
\$this->setData( '$variable_field', \$$variable_name );
\$this->$variable_field = \$$variable_name;
\$this->setDataChanges( TRUE );
return \$this;
CODE
                );
                $get_method->setBody(<<<CODE
return \$this->getData( '$variable_field' );
CODE
                );

            } else {

// set & get methods for everything else
                $set_method->setBody(<<<CODE
\$this->$variable_field = \$$variable_name;
return \$this;
CODE
                );
                $get_method->setBody(<<<CODE
return \$this->$variable_field;
CODE
                );
            }
        }

        $property_comment = DocBlockGenerator::fromArray(['tags' => [new Tag\PropertyTag($variable_name,
            [$type])]]);
        $property = PropertyGenerator::fromArray(['name' => $variable_name, 'flags' => $flags,
            'docblock' => $property_comment]);

        $this->class->addPropertyFromGenerator($property);
        $this->class->addMethodFromGenerator($set_method);
        $this->class->addMethodFromGenerator($get_method);
    }
}