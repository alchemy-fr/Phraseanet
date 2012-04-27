<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Media\Subdef\OptionType;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
interface OptionType
{
    const TYPE_RANGE = 'Range';
    const TYPE_ENUM = 'Enum';
    const TYPE_BOOLEAN = 'Boolean';
    const TYPE_MULTI = 'Multi';

    public function getDisplayName();

    public function getName();

    public function getType();

    public function getValue();
}
