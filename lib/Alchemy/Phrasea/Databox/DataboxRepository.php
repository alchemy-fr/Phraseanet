<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Databox;

interface DataboxRepository
{
    /**
     * @param int $id
     * @return \databox
     */
    public function find($id);

    /**
     * @return \databox[]
     */
    public function findAll();
}
