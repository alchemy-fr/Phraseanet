<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Application\Helper;

use Alchemy\Phrasea\Collection\CollectionService;

trait ApplicationBoxAware
{
    /** @var \appbox|callable */
    private $applicationBox;

    public function setApplicationBox($applicationBox)
    {
        if (!$applicationBox instanceof \appbox && !is_callable($applicationBox)) {
            throw new \InvalidArgumentException(sprintf(
                '%s expects parameter to be a "%s" instance or a callable, got "%s".',
                __METHOD__,
                \appbox::class,
                is_object($applicationBox) ? get_class($applicationBox) : gettype($applicationBox)
            ));
        }
        $this->applicationBox = $applicationBox;

        return $this;
    }

    /**
     * @return \appbox
     */
    public function getApplicationBox()
    {
        if ($this->applicationBox instanceof \appbox) {
            return $this->applicationBox;
        }

        if (null === $this->applicationBox && $this instanceof \Pimple && $this->offsetExists('phraseanet.appbox')) {
            $this->applicationBox = function () {
                return $this['phraseanet.appbox'];
            };
        }

        if (null === $this->applicationBox) {
            throw new \LogicException('Application box instance or locator was not set');
        }

        $instance = call_user_func($this->applicationBox);
        if (!$instance instanceof \appbox) {
            throw new \LogicException(sprintf(
                'Expects locator to return instance of "%s", got "%s"',
                \appbox::class,
                is_object($instance) ? get_class($instance) : gettype($instance)
            ));
        }
        $this->applicationBox = $instance;

        return $this->applicationBox;
    }

    /**
     * @return CollectionService
     */
    public function getCollectionService()
    {
        return $this['services.collection'];
    }

    /**
     * Find a registered Databoxes.
     *
     * @return \databox[]
     */
    public function getDataboxes()
    {
        return $this->getApplicationBox()->get_databoxes();
    }

    /**
     * Find a registered Databox by its id.
     *
     * @param int $id
     * @return \databox
     */
    public function findDataboxById($id)
    {
        return $this->getApplicationBox()->get_databox($id);
    }
}
