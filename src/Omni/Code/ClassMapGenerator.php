<?php

namespace Ls\Omni\Code;

use Ls\Omni\Service\Metadata;
use Zend\Code\Generator\MethodGenerator;

class ClassMapGenerator extends AbstractOmniGenerator
{

    /**
     * ClassMapGenerator constructor.
     * @param Metadata $metadata
     * @throws \Exception
     */
    public function __construct(Metadata $metadata)
    {
        parent::__construct($metadata);
    }

    /**
     * @return string
     */

    public function generate()
    {

        $body = '';
        foreach ($this->metadata->getEntities() as $entity_name => $entity) {
            $fqn = self::fqn($this->base_namespace, 'Entity', $entity->getElement()->getType());
            $fqn = str_replace('\\', '\\\\', $fqn);
            $body .= sprintf("\t\t'%1\$s' => '%2\$s',\n", $entity_name, $fqn);
        }
        $restriction_blacklist = ['char', 'duration', 'guid', 'StreamBody',
            //'NotificationStatus','OrderQueueStatusFilterType',
        ];
        foreach ($this->metadata->getRestrictions() as $restriction_name => $restriction) {
            if (array_search($restriction_name, $restriction_blacklist) === false) {
                $fqn = self::fqn($this->base_namespace, 'Entity', 'Enum', $restriction_name);
                $fqn = str_replace('\\', '\\\\', $fqn);
                $body .= sprintf("\t\t'%1\$s' => '%2\$s',\n", $restriction_name, $fqn);
            }
        }
        $map_method = new MethodGenerator();
        $map_method->setName('getClassMap');
        $map_method->setFinal(true);
        $map_method->setStatic(true);
        $map_method->setVisibility(MethodGenerator::FLAG_PROTECTED);
        $map_method->setBody(sprintf('return [%1$s];', $body));
        $this->class->setName('ClassMap');

        $this->class->setNamespaceName($this->base_namespace);
        $this->class->addMethodFromGenerator($map_method);

        return $this->file->generate();
    }
}
