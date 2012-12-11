<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Service\TaskManager;

use Alchemy\Phrasea\Core;
use Alchemy\Phrasea\Core\Service;
use Alchemy\Phrasea\Core\Service\ServiceAbstract;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * Define a Border Manager service which handles checks on files that comes in
 * Phraseanet
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class TaskManager extends ServiceAbstract
{
    /** `
     * `@var \Alchemy\Phrasea\Border\Manager
     */
    protected $TaskManagerConfiguration;

    /**
     * {@inheritdoc}
     */
    protected function init()
    {

        $this->TaskManagerConfiguration =  new ParameterBag($this->getOptions());
    }

    /**
     * Set and return a new Border Manager instance and set the proper checkers
     * according to the services configuration
     *
     * @return \Alchemy\Phrasea\Border\Manager
     */
    public function getDriver()
    {
        return $this->TaskManagerConfiguration;
    }

    /**
     * Return the type of the service
     * @return string
     */
    public function getType()
    {
        return 'task-manager';
    }

    /**
     * Define the mandatory option for the current services
     * @return array
     */
    public function getMandatoryOptions()
    {
        return array();
       // return array('enabled', 'checkers');
    }

}
