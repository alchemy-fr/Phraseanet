<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event;

use Alchemy\Phrasea\Model\Entities\LazaretFile;
use Symfony\Component\EventDispatcher\Event as SfEvent;

class LazaretEvent extends SfEvent
{
    private $file;

    public function __construct(LazaretFile $file)
    {
        $this->file = $file;
    }

    /**
     * @return LazaretFile
     */
    public function getFile()
    {
        return $this->file;
    }
}
