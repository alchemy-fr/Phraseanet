<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
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
   * @param Request $request
   * @return API_OAuth2_Form_DevApp
   */
  public function __construct(Request $request)
  {
    $this->name = $request->get('name', null);
    $this->description = $request->get('description', null);
    $this->website = $request->get('website', null);
    $this->callback = $request->get('callback', null);

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
   * @param string $callback
   * @return API_OAuth2_Form_DevApp
   */
  public function setName($name)
  {
    $this->name = $name;

    return $this;
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
   * @param string $callback
   * @return API_OAuth2_Form_DevApp
   */
  public function setDescription($description)
  {
    $this->description = $description;

    return $this;
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
   * @param string $callback
   * @return API_OAuth2_Form_DevApp
   */
  public function setWebsite($website)
  {
    $this->website = $website;

    return $this;
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
   * @param string $callback
   * @return API_OAuth2_Form_DevApp
   */
  public function setCallback($callback)
  {
    $this->callback = $callback;

    return $this;
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
    $metadata->addPropertyConstraint('website', new Constraints\NotBlank($blank));
    $metadata->addPropertyConstraint('website', new Constraints\Url($url));
    return;
  }

}