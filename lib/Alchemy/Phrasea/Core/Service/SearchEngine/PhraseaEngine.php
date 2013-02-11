<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Service\SearchEngine;

use Alchemy\Phrasea\SearchEngine\Phrasea\PhraseaEngine as PhraseaSearchEngine;
use Alchemy\Phrasea\Core\Service\ServiceAbstract;

class PhraseaEngine extends ServiceAbstract
{
    protected $searchEngine;

    protected function init()
    {
        $this->searchEngine = PhraseaSearchEngine::create($this->app);

        return $this;
    }

    public function getDriver()
    {
        return $this->searchEngine;
    }

    public function getType()
    {
        return 'phrasea';
    }

    public function getMandatoryOptions()
    {
        return array();
    }
}
