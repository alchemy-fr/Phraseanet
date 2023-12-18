<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Media\Subdef\OptionType;

interface OptionType
{
    const TYPE_RANGE = 'Range';
    const TYPE_ENUM = 'Enum';
    const TYPE_BOOLEAN = 'Boolean';
    const TYPE_MULTI = 'Multi';
    const TYPE_TEXT = 'Text';
    const TYPE_ENUM_BUTTON = 'EnumButton';

    public function getDisplayName();

    public function getName();

    public function getType();

    public function getValue();
}
