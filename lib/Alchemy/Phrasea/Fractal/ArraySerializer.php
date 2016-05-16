<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Fractal;

use League\Fractal\Pagination\CursorInterface;
use League\Fractal\Pagination\PaginatorInterface;
use League\Fractal\Resource\ResourceInterface;
use League\Fractal\Serializer\SerializerAbstract;

class ArraySerializer extends SerializerAbstract
{
    public function collection($resourceKey, array $data)
    {
        return array_values(array_filter($data));
    }

    public function item($resourceKey, array $data)
    {
        return $data;
    }

    public function null($resourceKey)
    {
        return null;
    }

    public function includedData(ResourceInterface $resource, array $data)
    {
        return $data;
    }

    public function meta(array $meta)
    {
        return [];
    }

    public function paginator(PaginatorInterface $paginator)
    {
        return [];
    }

    public function cursor(CursorInterface $cursor)
    {
        return [];
    }
}
