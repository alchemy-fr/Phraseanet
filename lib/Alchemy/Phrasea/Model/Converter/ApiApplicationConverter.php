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

use Alchemy\Phrasea\Model\Entities\ApiApplication;
use Alchemy\Phrasea\Model\Repositories\ApiApplicationRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ApiApplicationConverter implements ConverterInterface
{
    private $repository;

    public function __construct(ApiApplicationRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * {@inheritdoc}
     *
     * @return ApiApplication
     */
    public function convert($id)
    {
        if (null === $application = $this->repository->find((int) $id)) {
            throw new NotFoundHttpException(sprintf('Application %s not found.', $id));
        }

        return $application;
    }
}
