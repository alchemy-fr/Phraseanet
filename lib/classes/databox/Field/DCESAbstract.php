<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

abstract class databox_Field_DCESAbstract
{

  const Contributor = 'Contributor';
  const Coverage = 'Coverage';
  const Creator = 'Creator';
  const Date = 'Date';
  const Description = 'Description';
  const Format = 'Format';
  const Identifier = 'Identifier';
  const Language = 'Language';
  const Publisher = 'Publisher';
  const Relation = 'Relation';
  const Rights = 'Rights';
  const Source = 'Source';
  const Subject = 'Subject';
  const Title = 'Title';
  const Type = 'Type';

  /**
   *
   * @var string
   */
  protected $label;
  /**
   *
   * @var string
   */
  protected $definition;
  /**
   *
   * @var string
   */
  protected $URI;

  public function __construct()
  {
    return $this;
  }

  /**
   *
   * @return string
   */
  public function get_label()
  {
    return $this->label;
  }

  /**
   *
   * @return string
   */
  public function get_definition()
  {
    return $this->definition;
  }

  /**
   *
   * @return string
   */
  public function get_documentation_link()
  {
    return $this->URI;
  }

}
