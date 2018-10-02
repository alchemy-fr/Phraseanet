<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\DoctrineExtension;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

class TablePrefix
{
    private $namespace;
    private $prefix;

    /**
     * @param string $namespace namespace of classes to prefix
     * @param string $prefix
     */
    public function __construct($namespace, $prefix)
    {
        $this->namespace = trim((string)$namespace, '\\') . '\\';
        $this->prefix = (string)$prefix;
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        /** @var ClassMetadataInfo $classMetadata */
        $classMetadata = $eventArgs->getClassMetadata();
        if (substr($classMetadata->getName(), 0, strlen($this->namespace)) != $this->namespace) {
            return;
        }

        if (!$classMetadata->isInheritanceTypeSingleTable() || $classMetadata->getName() === $classMetadata->rootEntityName) {
            $classMetadata->setPrimaryTable(['name' => $this->prefix . $classMetadata->getTableName()]);
        }

        foreach ($classMetadata->getAssociationMappings() as $fieldName => $mapping) {
            if ($mapping['type'] == ClassMetadataInfo::MANY_TO_MANY && $mapping['isOwningSide']) {
                $mappedTableName = $mapping['joinTable']['name'];
                $classMetadata->associationMappings[$fieldName]['joinTable']['name'] = $this->prefix . $mappedTableName;
            }
        }

    }
}
