<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Service;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Service\Exception\MissingParameters;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
abstract class ServiceAbstract implements ServiceInterface
{
    protected $app;
    protected $options;

    final public function __construct(Application $app, Array $options)
    {
        $this->app = $app;
        $this->options = $options;

        $mandatory = $this->getMandatoryOptions();

        if ($mandatory !== array_intersect($mandatory, array_keys($options))) {
            throw new MissingParameters(
                sprintf(
                    'Missing parameters %s'
                    , implode(', ', array_diff($mandatory, array_keys($options)))
                )
            );
        }

        $this->init();
    }

    protected function init()
    {
        return;
    }

    /**
     *
     * @return Array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     *
     * @return Array
     */
    public function getMandatoryOptions()
    {
        return array();
    }
}
