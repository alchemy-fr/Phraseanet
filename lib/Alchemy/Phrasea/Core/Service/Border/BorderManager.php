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

use Alchemy\Phrasea\Border\Manager;
use Alchemy\Phrasea\Core\Service\ServiceAbstract;

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
     * {@inheritdoc}
     */
    protected function init()
    {
        $borderManager = new Manager($this->app);

        if ($this->app['xpdf.pdf2text']) {
            $borderManager->setPdfToText($this->app['xpdf.pdf2text']);
        }

        $options = $this->getOptions();

        $registeredCheckers = array();

        if ( ! ! $options['enabled']) {
            foreach ($options['checkers'] as $checker) {

                if ( ! isset($checker['type'])) {
                    $this->addUnregisteredCheck(null, 'No type defined');
                    continue;
                }

                $type = $checker['type'];

                if (isset($checker['enabled']) && $checker['enabled'] !== true) {
                    $this->addUnregisteredCheck($type, 'Checker is disabled');
                    continue;
                }

                $className = sprintf('\\Alchemy\\Phrasea\\Border\\%s', $checker['type']);

                if ( ! class_exists($className)) {
                    $this->addUnregisteredCheck($type, sprintf('Unknow checker type "%s"', $type));
                    continue;
                }

                $options = array();

                if (isset($checker['options']) && is_array($checker['options'])) {
                    $options = $checker['options'];
                }

                try {
                    $checkerObj = new $className($this->app, $options);
                    if (isset($checker['databoxes'])) {

                        $databoxes = array();
                        foreach ($checker['databoxes'] as $sbas_id) {
                            try {
                                $databoxes[] = $this->app['phraseanet.appbox']->get_databox($sbas_id);
                            } catch (\Exception $e) {
                                throw new \InvalidArgumentException('Invalid databox option');
                            }
                        }

                        $checkerObj->restrictToDataboxes($databoxes);
                    }
                    if (isset($checker['collections'])) {

                        $collections = array();
                        foreach ($checker['collections'] as $base_id) {
                            try {
                                $collections[] = \collection::get_from_base_id($this->app, $base_id);
                            } catch (\Exception $e) {
                                throw new \InvalidArgumentException('Invalid collection option');
                            }
                        }

                        $checkerObj->restrictToCollections($collections);
                    }
                    $registeredCheckers[] = $checkerObj;
                } catch (\InvalidArgumentException $e) {
                    $this->addUnregisteredCheck($type, $e->getMessage());
                } catch (\LogicException $e) {
                    $this->addUnregisteredCheck($type, $e->getMessage());
                }
            }

            $borderManager->registerCheckers($registeredCheckers);
        }

        $this->borderManager = $borderManager;
    }

    /**
     * Set and return a new Border Manager instance and set the proper checkers
     * according to the services configuration
     *
     * @return \Alchemy\Phrasea\Border\Manager
     */
    public function getDriver()
    {
        return $this->borderManager;
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
