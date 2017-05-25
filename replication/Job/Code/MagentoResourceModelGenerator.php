<?php
namespace Ls\Replication\Job\Code;


use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\GeneratorInterface;

class MagentoResourceModelGenerator implements GeneratorInterface
{
    /** @var  string */
    private $entity_fqn;
    /** @var FileGenerator */
    private $file;
    /** @var ClassGenerator */
    private $class;


    public function __construct ( $entity_fqn ) {
        $this->entity_fqn = $entity_fqn;
        $this->file = new FileGenerator();
        $this->class = new ClassGenerator();
        $this->file->setClass( $this->class );
    }

    /**
     * @return string
     */
    public function generate () {

        return $this->file->generate();
    }
}
