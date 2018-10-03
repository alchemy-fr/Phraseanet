<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Converter;

use Alchemy\Phrasea\Model\Entities\Task;
use Alchemy\Phrasea\Model\Repositories\TaskRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TaskConverter implements ConverterInterface
{
    private $repository;

    public function __construct(TaskRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * {@inheritdoc}
     *
     * @return Task
     */
    public function convert($id)
    {
        if (null === $task = $this->repository->find((int) $id)) {
            throw new NotFoundHttpException(sprintf('Task %s not found.', $id));
        }

        return $task;
    }
}
