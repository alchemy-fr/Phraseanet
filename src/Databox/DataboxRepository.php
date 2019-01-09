<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Databox;

interface DataboxRepository
{
    /**
     * @param int $id
     * @return \App\Utils\databox
     */
    public function find($id);

    /**
     * @return \App\Utils\databox[]
     */
    public function findAll();

    /**
     * @param \App\Utils\databox $databox
     */
    public function save(\App\Utils\databox $databox);

    /**
     * @param \App\Utils\databox $databox
     */
    public function delete(\App\Utils\databox $databox);

    /**
     * @param \App\Utils\databox $databox
     */
    public function unmount(\App\Utils\databox $databox);

    /**
     * @param $host
     * @param $port
     * @param $user
     * @param $password
     * @param $dbname
     *
     * @return \App\Utils\databox
     */
    public function mount($host, $port, $user, $password, $dbname);

    /**
     * @param $host
     * @param $port
     * @param $user
     * @param $password
     * @param $dbname
     *
     * @return \\App\Utils\databox
     */
    public function create($host, $port, $user, $password, $dbname);
}
