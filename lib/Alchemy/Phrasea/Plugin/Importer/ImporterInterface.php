<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Plugin\Importer;

use Alchemy\Phrasea\Plugin\Exception\ImportFailureException;

interface ImporterInterface
{
    /**
     * @param $source
     * @param $target
     *
     * @throws ImportFailureException In case the import failed
     */
    public function import($source, $target);
}
