<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Vocabulary\ControlProvider;

use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Vocabulary\Term;

interface ControlProviderInterface
{
    /**
     * ControlProvider class should be named like {type}Provider
     * in the ControlProvider namespace
     *
     * @return string the type of the ControlProvider
     */
    public static function getType();

    /**
     * @return string a simple i18n word representing this vocabulary
     */
    public function getName();

    /**
     * @return boolean validate an $id in the vocabulary
     */
    public function validate($id);

    /**
     * @return string     returns the value corresponding to an id
     */
    public function getValue($id);

    /**
     * @return mixed      returns the actual resource corresponding to an id
     * @throws \Exception if the $id is invalid
     */
    public function getResource($id);

    /**
     * Find matching Term in the vocabulary repository
     *
     * @param string   $query      A scalar query
     * @param User     $for_user   The user doing the query
     * @param \databox $on_databox The databox where vocabulary should be requested
     *
     * @return Term[]
     */
    public function find($query, User $for_user, \databox $on_databox);
}
