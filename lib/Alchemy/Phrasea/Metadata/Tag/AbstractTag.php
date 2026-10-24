<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Metadata\Tag;

use Alchemy\Phrasea\Metadata\TagInterface;
use PHPExiftool\Driver\AbstractTagGroup;

abstract class AbstractTag extends AbstractTagGroup implements TagInterface
{
    public function getTagname(): string
    {
        return $this->getId();
    }

    public function getGroupName(): string
    {
        $parts = explode(':', $this->getId(), 2);

        return count($parts) === 2 ? $parts[0] : '';
    }

    public function getMinLength(): int
    {
        return 0;
    }
}
