<?php

namespace Ls\Omni\Code;

use Laminas\Code\Generator\MethodGenerator;

class ClassMapGenerator extends AbstractOmniGenerator
{
    /**
     * @var array
     */
    public array $customClassMap;

    /**
     * Get custom class map
     *
     * @return array
     */
    public function getCustomClassMap(): array
    {
        return $this->customClassMap;
    }

    /**
     * Set custom class map
     *
     * @param array $classMap
     * @return void
     */
    public function setCustomClassMap(array $classMap)
    {
        $this->customClassMap = $classMap;
    }

    /**
     * Generates a class map for the entities and restrictions.
     *
     * It maps each entity and restriction to its fully qualified namespace.
     *
     * @return string The generated class file as a string.
     */
    public function generate()
    {
        // Initialize the class map body as an empty string.
        $classMapBody = '';

        // Loop through all entities in the metadata and add their FQNs to the class map.
        foreach ($this->metadata->getEntities() as $entityName => $entity) {
            // Generate the fully qualified name (FQN) for the entity.
            $fqn = self::fqn(
                $this->baseNamespace,
                'Entity',
                preg_replace('/[-._]/', '', $entity->getElement()->getType())
            );
            $fqn = str_replace('\\', '\\\\', $fqn);

            // Append the entity's name and FQN to the class map.
            $classMapBody .= sprintf("\t\t'%1\$s' => '%2\$s',\n", $entityName, $fqn);
        }

        // Define a blacklist of restrictions to exclude from the class map.
        $restrictionBlacklist = [
            'char',
            'duration',
            'guid',
            'StreamBody',
            //'NotificationStatus', 'OrderQueueStatusFilterType',
        ];

        // Loop through all restrictions in the metadata and add them to the class map if not blacklisted.
        foreach ($this->metadata->getRestrictions() as $restrictionName => $restriction) {
            // Check if the restriction is not in the blacklist.
            if (array_search($restrictionName, $restrictionBlacklist) === false) {
                // Generate the fully qualified name (FQN) for the restriction.
                $fqn = self::fqn($this->baseNamespace, 'Entity', 'Enum', $restrictionName);
                $fqn = str_replace('\\', '\\\\', $fqn);

                // Append the restriction's name and FQN to the class map.
                $classMapBody .= sprintf("\t\t'%1\$s' => '%2\$s',\n", $restrictionName, $fqn);
            }
        }

        foreach ($this->getCustomClassMap() as $unsanitizedName => $sanitizedName) {
            $fqn = self::fqn(
                $this->baseNamespace,
                'Entity',
                $sanitizedName
            );

            $fqn = str_replace('\\', '\\\\', $fqn);

            $classMapBody .= sprintf("\t\t'%1\$s' => '%2\$s',\n", $unsanitizedName, $fqn);
        }

        // Create the 'getClassMap' method for the class map generation.
        // @codingStandardsIgnoreLine
        $classMapMethod = new MethodGenerator();
        $classMapMethod->setName('getClassMap');
        $classMapMethod->setFinal(true);
        $classMapMethod->setStatic(true);
        $classMapMethod->setVisibility(MethodGenerator::FLAG_PROTECTED);
        $classMapMethod->setBody(sprintf('return [%1$s];', $classMapBody));

        // Set the name and namespace for the class.
        $this->class->setName('ClassMap');
        $this->class->setNamespaceName($this->baseNamespace);

        // Add the generated method to the class.
        $this->class->addMethodFromGenerator($classMapMethod);

        // Generate and return the final file content.
        return $this->file->generate();
    }
}
