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

class Importer
{
    private $importers;
    private $strategy;

    public function __construct(ImportStrategy $strategy, $importers)
    {
        $this->importers = $importers;
        $this->strategy = $strategy;
    }

    /**
     *
     * @param string $source
     * @param string $target
     *
     * @throws ImportFailureException
     */
    public function import($source, $target)
    {
        $strategy = $this->strategy->detect($source);

        if (!isset($this->importers[$strategy])) {
            throw new ImportFailureException(sprintf('Unable to get an import for source `%s`', $source));
        }

        $this->importers[$strategy]->import($source, $target);
    }
}
