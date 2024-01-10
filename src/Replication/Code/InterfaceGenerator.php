<?php
// @codingStandardsIgnoreFile

namespace Ls\Replication\Code;

use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\Exception;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use Laminas\Code\Reflection\ClassReflection;

/**
 * Class InterfaceGenerator
 * @package Ls\Replication\Code
 */
class InterfaceGenerator extends ClassGenerator
{
    const OBJECT_TYPE = 'interface';
    const IMPLEMENTS_KEYWORD = 'extends';

    /**
     * Build a Code Generation Php Object from a Class Reflection
     *
     * @param ClassReflection $classReflection
     *
     * @return InterfaceGenerator
     */
    public static function fromReflection(ClassReflection $classReflection)
    {
        if (!$classReflection->isInterface()) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Class %s is not a interface',
                $classReflection->getName()
            ));
        }

        // class generator
        $cg      = new static($classReflection->getName());
        $methods = [];

        $cg->setSourceContent($cg->getSourceContent());
        $cg->setSourceDirty(false);

        if ($classReflection->getDocComment() != '') {
            $cg->setDocBlock(DocBlockGenerator::fromReflection($classReflection->getDocBlock()));
        }

        // set the namespace
        if ($classReflection->inNamespace()) {
            $cg->setNamespaceName($classReflection->getNamespaceName());
        }

        foreach ($classReflection->getMethods() as $reflectionMethod) {
            $className = ($cg->getNamespaceName())
                ? $cg->getNamespaceName() . '\\' . $cg->getName()
                : $cg->getName();

            if ($reflectionMethod->getDeclaringClass()->getName() == $className) {
                $methods[] = MethodGenerator::fromReflection($reflectionMethod);
            }
        }

        foreach ($classReflection->getConstants() as $name => $value) {
            $cg->addConstant($name, $value);
        }

        $cg->addMethods($methods);

        return $cg;
    }

    /**
     * Generate from array
     *
     * @configkey name           string        [required] Class Name
     * @configkey filegenerator  FileGenerator File generator that holds this class
     * @configkey namespacename  string        The namespace for this class
     * @configkey docblock       string        The docblock information
     * @configkey constants
     * @configkey methods
     *
     * @param array $array
     *
     * @return InterfaceGenerator
     * @throws Exception\InvalidArgumentException
     *
     */
    public static function fromArray(array $array)
    {
        if (!isset($array['name'])) {
            throw new Exception\InvalidArgumentException(
                'Class generator requires that a name is provided for this object'
            );
        }

        $cg = new static($array['name']);
        foreach ($array as $name => $value) {
            switch (strtolower(str_replace(['.', '-', '_'], '', $name))) {
                case 'containingfile':
                    $cg->setContainingFileGenerator($value);
                    break;
                case 'namespacename':
                    $cg->setNamespaceName($value);
                    break;
                case 'docblock':
                    $docBlock =
                        ($value instanceof DocBlockGenerator) ? $value : DocBlockGenerator::fromArray($value);
                    $cg->setDocBlock($docBlock);
                    break;
                case 'methods':
                    $cg->addMethods($value);
                    break;
                case 'constants':
                    $cg->addConstants($value);
                    break;
            }
        }

        return $cg;
    }

    /**
     * {@inheritdoc}
     */
    public function addPropertyFromGenerator(PropertyGenerator $property)
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addMethodFromGenerator(MethodGenerator $method)
    {
        return parent::addMethodFromGenerator($method);
    }

    /**
     * {@inheritdoc}
     */
    public function setExtendedClass($extendedClass)
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setAbstract($isAbstract)
    {
        return $this;
    }
}
