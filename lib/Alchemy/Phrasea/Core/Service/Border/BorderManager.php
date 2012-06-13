<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Service\Border;

use Alchemy\Phrasea\Border;
use Alchemy\Phrasea\Core;
use Alchemy\Phrasea\Core\Service;
use Alchemy\Phrasea\Core\Service\ServiceAbstract;
use Alchemy\Phrasea\Core\Service\ServiceInterface;

/**
 * Define a Border Manager service which handles checks on files that comes in
 * Phraseanet
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class BorderManager extends ServiceAbstract
{
    /** `
     * `@var \Alchemy\Phrasea\Border\Manager
     */
    protected $borderManager;

    /**
     * Get all unregistered checkers due to bad configuration
     * @var array
     */
    protected $unregisteredCheckers = array();

    /**
     * Set and return a new Border Manager instance and set the proper checkers
     * according to the services configuration
     *
     * @return \Alchemy\Phrasea\Border\Manager
     */
    public function getDriver()
    {
        $borderManager = new Border\Manager($this->core['EM'], $this->core['monolog']);

        $options = $this->getOptions();

        $registeredCheckers = array();

        if ( ! ! $options['enabled']) {
            $checkers = $options['checkers'];
            foreach ($checkers as $checker) {
                if (isset($checker['enabled']) && isset($checker['type'])) {
                    $type = (string) $checker['type'];
                    $className = sprintf('\\Alchemy\\Phrasea\\Border\\%s', $type);

                    if ( ! class_exists($className)) {
                        $this->addUnregisteredCheck($type, sprintf('Unknow checker type "%s"', $type));
                    }

                    if ( ! ! $checker['enabled']) {
                        $options = array();

                        if (isset($checker['options']) && is_array($checker['options'])) {
                            $options = $checker['options'];
                        }

                        try {
                            $checker = new $className($options);
                            $registeredCheckers[] = $checker;
                        } catch (\InvalidArgumentException $e) {
                            $this->addUnregisteredCheck($type, $e->getMessage());
                        }
                    }
                }
            }

            $borderManager->registerCheckers($registeredCheckers);
        }

        return $borderManager;
    }

    /**
     * Return the type of the service
     * @return string
     */
    public function getType()
    {
        return 'border';
    }

    /**
     * Define the mandatory option for the current services
     * @return array
     */
    public function getMandatoryOptions()
    {
        return array('enabled', 'checkers');
    }

    /**
     * Return all unregistered Checkers
     * @return array
     */
    public function getUnregisteredCheckers()
    {
        return $this->unregisteredCheckers;
    }

    /**
     * Add an unregistered check entry
     *
     * @param string $type
     * @param string $message
     */
    private function addUnregisteredCheck($type, $message)
    {
        $this->unregisteredCheckers[] = array(
            'checker' => $type,
            'message' => $message
        );
    }
}
