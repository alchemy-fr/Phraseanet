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

class ImportStrategy
{
    public function detect($source)
    {
        switch (true) {
            case file_exists($source) && is_dir($source):
                return 'plugins.importer.folder-importer';
            default:
                throw new ImportFailureException(sprintf('Unable to detect source type for `%s`', $source));
        }
    }
}
