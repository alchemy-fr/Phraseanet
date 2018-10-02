<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Types;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class BinaryString extends Type
{
    const BINARY_STRING = 'binary_string';

    public function getName()
    {
        return static::BINARY_STRING;
    }

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        if ($platform->getName() === 'mysql') {
            return $platform->getVarcharTypeDeclarationSQL($fieldDeclaration)." ". $platform->getCollationFieldDeclaration('utf8_bin');
        }

        return $platform->getVarcharTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultLength(AbstractPlatform $platform)
    {
        return $platform->getVarcharDefaultLength();
    }
}
