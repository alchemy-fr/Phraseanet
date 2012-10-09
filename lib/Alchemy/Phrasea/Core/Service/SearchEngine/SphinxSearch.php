<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Service\SearchEngine;

use Alchemy\Phrasea\SearchEngine\SphinxSearch\SphinxSearchEngine;
use Alchemy\Phrasea\Core\Service\ServiceAbstract;

class SphinxSearch extends ServiceAbstract
{
    protected $searchEngine;

    protected function init()
    {
        $options = $this->getOptions();

        $this->searchEngine = new SphinxSearchEngine($this->app, $options['host'], $options['port'], $options['rt_host'], $options['rt_port']);

        return $this;
    }

    public function getDriver()
    {
        return $this->searchEngine;
    }

    public function getType()
    {
        return 'sphinx-search';
    }

    public function getMandatoryOptions()
    {
        return array('host', 'port', 'rt_host', 'rt_port');
    }
}
