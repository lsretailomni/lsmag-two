<?php
namespace Ls\Replication\Job\Code;


use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\GeneratorInterface;

class ModuleVersionGenerator implements GeneratorInterface
{
    /** @var  string */
    private $version;
    /** @var FileGenerator */
    private $file;
    /** @var ClassGenerator */
    private $class;


    public function __construct () {
        $this->file = new FileGenerator();
        $this->class = new ClassGenerator();
        $this->file->setClass( $this->class );
    }

    public function getVersion () {
        return $this->version;
    }

    /**
     * @return string
     */
    public function generate () {

        return $this->file->generate();
    }
}
