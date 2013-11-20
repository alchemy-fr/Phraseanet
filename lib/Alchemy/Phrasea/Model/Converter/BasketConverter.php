<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Converter;

use Alchemy\Phrasea\Model\Entities\Basket;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BasketConverter implements ConverterInterface
{
    private $om;

    public function __construct(ObjectManager $om)
    {
        $this->om = $om;
    }

    /**
     * {@inheritdoc}
     *
     * @return Basket
     */
    public function convert($id)
    {
        if (null === $basket = $this->om->find('Alchemy\Phrasea\Model\Entities\Basket', (int) $id)) {
            throw new NotFoundHttpException(sprintf('Basket %s not found.', $id));
        }

        return $basket;
    }
}
