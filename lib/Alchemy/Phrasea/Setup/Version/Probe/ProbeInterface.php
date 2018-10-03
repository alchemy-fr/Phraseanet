<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup\Version\Probe;

use Alchemy\Phrasea\Setup\Version\Migration\MigrationInterface;

interface ProbeInterface
{
    /**
     * @return Boolean
     */
    public function isMigrable();

    /**
     * @return MigrationInterface
     */
    public function getMigration();
}
