<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup\Probe;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Setup\RequirementCollectionInterface;

/**
 * A probe is used to probe the system when installed and configured.
 */
interface ProbeInterface extends RequirementCollectionInterface
{
    /**
     * @param Application $app
     */
    public static function create(Application $app);
}
