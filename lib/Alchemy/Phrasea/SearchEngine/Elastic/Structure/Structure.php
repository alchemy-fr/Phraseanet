<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic\Structure;

interface Structure
{
    /**
     * @return mixed
     */
    public function getDataboxes();

    /**
     * @return Field[]
     */
    public function getAllFields();

    /**
     * @return Field[]
     */
    public function getUnrestrictedFields();

    /**
     * @return Field[]
     */
    public function getPrivateFields();

    /**
     * @return Field[]
     */
    public function getThesaurusEnabledFields();

    /**
     * @return Field[]
     */
    public function getDateFields();

    /**
     * @param string $name
     * @return null|Field
     */
    public function get($name);

    /**
     * @param string $name
     * @return string|null
     */
    public function typeOf($name);

    /**
     * @param string $name
     * @return bool
     * @throws \DomainException
     */
    public function isPrivate($name);

    /**
     * @return Flag[]
     */
    public function getAllFlags();

    /**
     * @param string $name
     * @return Flag
     */
    public function getFlagByName($name);

    /**
     * @return Tag[]
     */
    public function getMetadataTags();

    /**
     * @param string $name
     * @return Tag|null
     */
    public function getMetadataTagByName($name);
}
