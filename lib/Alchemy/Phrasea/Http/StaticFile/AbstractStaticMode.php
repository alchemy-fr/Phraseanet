<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Http\StaticFile;

use Alchemy\Phrasea\Http\AbstractServerMode;
use Alchemy\Phrasea\Http\StaticFile\Symlink\SymLinker;

abstract class AbstractStaticMode extends AbstractServerMode
{
    protected $symlinker;

    public function __construct(array $mapping, SymLinker $symlinker)
    {
        $this->symlinker = $symlinker;

        parent::__construct($mapping);
    }
}
