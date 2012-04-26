<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 *
 * @package     OAuth2 Connector
 *
 * @see         http://oauth.net/2/
 * @uses        http://code.google.com/p/oauth2-php/
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints;

class API_OAuth2_Form_DevAppDesktop
{
    /**
     *
     * @var string
     */
    public $name;

    /**
     *
     * @var string
     */
    public $description;

    /**
     *
     * @var string
     */
    public $website;

    /**
     *
     * @var string
     */
    public $callback;

    /**
     *
     * @var string
     */
    public $type;
    public $scheme_website;
    public $urlwebsite;

    /**
     *
     * @param Request $request
     * @return API_OAuth2_Form_DevApp
     */
    public function __construct(Request $request)
    {
        $this->name = $request->get('name', '');
        $this->description = $request->get('description', '');
        $this->scheme_website = $request->get('scheme-website', 'http://');
        $this->website = $request->get('website', '');
        $this->callback = API_OAuth2_Application::NATIVE_APP_REDIRECT_URI;
        $this->type = API_OAuth2_Application::DESKTOP_TYPE;

        $this->urlwebsite = $this->scheme_website . $this->website;

        return $this;
    }

    /**
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     *
     * @return string
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     *
     * @return string
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    public function getSchemeWebsite()
    {
        return $this->scheme_website;
    }

    public function getUrlwebsite()
    {
        return $this->urlwebsite;
    }

    public function getSchemeCallback()
    {
        return '';
    }

    /**
     *
     * @param ClassMetadata $metadata
     * @return API_OAuth2_Form_DevApp
     */
    static public function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $blank = array('message' => _('Cette valeur ne peut être vide'));
        $url = array('message' => _('Url non valide'));

        $metadata->addPropertyConstraint('name', new Constraints\NotBlank($blank));
        $metadata->addPropertyConstraint('description', new Constraints\NotBlank($blank));
        $metadata->addPropertyConstraint('urlwebsite', new Constraints\NotBlank($blank));
        $metadata->addPropertyConstraint('urlwebsite', new Constraints\Url($url));

        return;
    }
}
