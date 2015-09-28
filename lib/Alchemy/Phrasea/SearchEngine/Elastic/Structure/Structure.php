<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Structure;

interface Structure
{
    public function getAllFields();
    public function getUnrestrictedFields();
    public function getPrivateFields();
    public function getFacetFields();
    public function getThesaurusEnabledFields();
    public function getDateFields();

    /**
     * @param string $name
     * @return null|Field
     */
    public function get($name);
    public function typeOf($name);

    /**
     * @param $name
     * @return bool
     * @throws \DomainException
     */
    public function isPrivate($name);

    public function getAllFlags();
    public function getFlagByName($name);
}
