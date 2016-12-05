<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command;

use Alchemy\Phrasea\Application;

interface CommandInterface
{
    /**
     * Inject the container in the command.
     *
     * @param Application $container
     */
    public function setContainer(Application $container);

    /**
     * Factory for the command.
     *
     * @deprecated Will be removed in a future release.
     */
    public static function create();
}
