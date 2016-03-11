<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Authentication\Provider\Token;

use Alchemy\Phrasea\Exception\InvalidArgumentException;

class Identity
{
    const PROPERTY_ID = 'id';
    const PROPERTY_IMAGEURL = 'image_url';
    const PROPERTY_EMAIL = 'email';
    const PROPERTY_FIRSTNAME = 'first_name';
    const PROPERTY_LASTNAME = 'last_name';
    const PROPERTY_USERNAME = 'username';
    const PROPERTY_COMPANY = 'company';

    private $data = [
        self::PROPERTY_ID        => null,
        self::PROPERTY_IMAGEURL  => null,
        self::PROPERTY_EMAIL     => null,
        self::PROPERTY_FIRSTNAME => null,
        self::PROPERTY_LASTNAME  => null,
        self::PROPERTY_USERNAME  => null,
        self::PROPERTY_COMPANY   => null,
    ];

    public function __construct(array $data = [])
    {
        $this->data = array_merge($this->data, $data);
    }

    /**
     * Returns an associated array of the current identity properies.
     *
     * @return array
     */
    public function all()
    {
        return array_filter($this->data);
    }

    /**
     * Returns the display name (first and last name)
     *
     * @return string
     */
    public function getDisplayName()
    {
        $data = array_filter([
            $this->get(self::PROPERTY_FIRSTNAME),
            $this->get(self::PROPERTY_LASTNAME),
        ]);

        return implode(' ', $data);
    }

    /**
     * Returns the id
     *
     * @return string
     */
    public function getId()
    {
        return $this->get(self::PROPERTY_ID);
    }

    /**
     * Returns the first name
     *
     * @return string
     */
    public function getFirstname()
    {
        return $this->get(self::PROPERTY_FIRSTNAME);
    }

    /**
     * Returns the last name
     *
     * @return string
     */
    public function getLastname()
    {
        return $this->get(self::PROPERTY_LASTNAME);
    }

    /**
     * Returns the image URI
     *
     * @return string
     */
    public function getImageURI()
    {
        return $this->get(self::PROPERTY_IMAGEURL);
    }

    /**
     * Returns the email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->get(self::PROPERTY_EMAIL);
    }

    /**
     * Returns the username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->get(self::PROPERTY_USERNAME);
    }

    /**
     * Returns the company name
     *
     * @return string
     */
    public function getCompany()
    {
        return $this->get(self::PROPERTY_COMPANY);
    }

    /**
     * Returns true if the property exists
     *
     * @param $property string
     *
     * @return boolean
     */
    public function has($property)
    {
        return isset($this->data[$property]);
    }

    /**
     * Get a property value
     *
     * @param $property string
     *
     * @return string
     *
     * @throws InvalidArgumentException In case the property does not exists
     */
    public function get($property)
    {
        if (!array_key_exists($property, $this->data)) {
            throw new InvalidArgumentException(sprintf('Property %s does not exist', $property));
        }

        return $this->data[$property];
    }

    /**
     * Set a property
     *
     * @param $property string
     * @param $value    string
     *
     * @return Identity
     */
    public function set($property, $value)
    {
        $this->data[$property] = $value;

        return $this;
    }

    /**
     * Removes the property
     *
     * @return string The value that was stored for the given property
     */
    public function remove($property)
    {
        if (!array_key_exists($property, $this->data)) {
            throw new InvalidArgumentException(sprintf('Property %s does not exist', $property));
        }

        $value = $this->data[$property];
        unset($this->data[$property]);

        return $value;
    }
}
