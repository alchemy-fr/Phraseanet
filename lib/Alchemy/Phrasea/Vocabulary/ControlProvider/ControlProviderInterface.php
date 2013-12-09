<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Vocabulary\ControlProvider;

interface ControlProviderInterface
{

    /**
     * @return the type of the ControlProvider
     *
     * ControlProvider class should be named like {type}Provider
     * in the ControlProvider namespace
     */
    public static function getType();

    /**
     * @return stringa simple i18n word to reprsent this vocabullary
     */
    public function getName();

    /**
     * @return boolean validate an $id in the vocabulary
     */
    public function validate($id);

    /**
     * @return string     returns the value corresponding to an id
     * @throws \Exception if the $id is invalid
     */
    public function getValue($id);

    /**
     * @return mixed      returns the actual ressource corresponding to an id
     * @throws \Exception if the $id is invalid
     */
    public function getRessource($id);

    /**
     * Find matching Term in the vocabulary repository
     *
     * @param string        $query      A scalar quaery
     * @param \User_Adapter $for_user   The user doing the query
     * @param \databox      $on_databox The databox where vocabulary should be requested
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function find($query, \User_Adapter $for_user, \databox $on_databox);
}
