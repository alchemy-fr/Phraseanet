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

use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Repositories\BasketRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BasketConverter implements ConverterInterface
{
    private $repository;

    public function __construct(BasketRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * {@inheritdoc}
     *
     * @return Basket
     */
    public function convert($id)
    {
        /** @var Basket $basket */
        if ( ($basket = $this->repository->find((int) $id)) === null) {
            throw new NotFoundHttpException(sprintf('Basket %s not found.', $id));
        }

        return $basket;
    }
}
