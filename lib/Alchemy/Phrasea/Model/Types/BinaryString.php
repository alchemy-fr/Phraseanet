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
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class BinaryString extends Type
{
    const BINARY_STRING = 'binary_string';

    public function getName()
    {
        return static::BINARY_STRING;
    }

    /**
     * {@inheritdoc}
     *
     * @see: https://blog.vandenbrand.org/2015/06/25/creating-a-custom-doctrine-dbal-type-the-right-way/
     *     about the reason of the COMMENT in the column
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        if ($platform->getName() === 'mysql') {
            /** @var MySqlPlatform $platform */
            return $platform->getVarcharTypeDeclarationSQL($fieldDeclaration)
                // . " CHARACTER SET utf8"
                . " " . $platform->getColumnCollationDeclarationSQL('utf8_bin')
                . " COMMENT '(DC2Type:binary_string)'"
                ;
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

    /**
     * @inheritdoc
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
