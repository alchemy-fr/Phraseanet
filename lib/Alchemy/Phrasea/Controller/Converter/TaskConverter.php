<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Converter;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Doctrine\Common\Persistence\ObjectManager;
use Alchemy\Phrasea\Model\Entities\Task;

class TaskConverter implements ConverterInterface
{
    private $om;

    public function __construct(ObjectManager $om)
    {
        $this->om = $om;
    }

    /**
     * {@inheritdoc}
     *
     * @return Task
     */
    public function convert($id)
    {
        if (null === $task = $this->om->find('Alchemy\Phrasea\Model\Entities\Task', (int) $id)) {
            throw new NotFoundHttpException(sprintf('Task %s not found.', $id));
        }

        return $task;
    }
}
