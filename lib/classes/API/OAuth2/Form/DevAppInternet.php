<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Model\Entities\ApiApplication;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints;

class API_OAuth2_Form_DevAppInternet
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
    public $scheme_website;
    public $scheme_callback;
    public $urlwebsite;
    public $urlcallback;

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->name = $request->get('name', '');
        $this->description = $request->get('description', '');
        $this->website = $request->get('website', '');
        $this->callback = $request->get('callback', '');
        $this->scheme_website = $request->get('scheme-website', 'http://');
        $this->scheme_callback = $request->get('scheme-callback', 'http://');
        $this->type = ApiApplication::WEB_TYPE;

        $this->urlwebsite = sprintf('%s%s', $this->scheme_website, $this->website);
        $this->urlcallback =  sprintf('%s%s', $this->scheme_callback, $this->callback);

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

    public function getSchemeCallback()
    {
        return $this->scheme_callback;
    }

    public function getUrlwebsite()
    {
        return $this->urlwebsite;
    }

    public function getUrlcallback()
    {
        return $this->urlcallback;
    }

    /**
     *
     * @param  ClassMetadata          $metadata
     * @return API_OAuth2_Form_DevApp
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('name', new Constraints\NotBlank());
        $metadata->addPropertyConstraint('description', new Constraints\NotBlank());
        $metadata->addPropertyConstraint('urlwebsite', new Constraints\NotBlank());
        $metadata->addPropertyConstraint('urlwebsite', new Constraints\Url());
        $metadata->addPropertyConstraint('urlcallback', new Constraints\NotBlank());
        $metadata->addPropertyConstraint('urlcallback', new Constraints\Url());

        return;
    }
}
