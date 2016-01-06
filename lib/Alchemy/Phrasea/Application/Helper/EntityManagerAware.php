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

use Doctrine\ORM\EntityManagerInterface;

trait EntityManagerAware
{
    private $entityManager;

    /**
     * Set Locator to use to locate EntityManager
     *
     * @param callable $locator
     * @return $this
     */
    public function setEntityManagerLocator(callable $locator)
    {
        $this->entityManager = $locator;

        return $this;
    }

    /**
     * @return EntityManagerInterface
     */
    public function getEntityManager()
    {
        if ($this->entityManager instanceof EntityManagerInterface) {
            return $this->entityManager;
        }

        if (null === $this->entityManager) {
            throw new \LogicException('Entity Manager locator was not set');
        }

        $instance = call_user_func($this->entityManager);
        if (!$instance instanceof EntityManagerInterface) {
            throw new \LogicException(sprintf(
                'Expects locator to return instance of "%s", got "%s"',
                EntityManagerInterface::class,
                is_object($instance) ? get_class($instance) : gettype($instance)
            ));
        }
        $this->entityManager = $instance;

        return $this->entityManager;
    }
}
