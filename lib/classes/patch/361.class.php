<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use DoctrineExtensions\Paginate\Paginate;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class patch_361 implements patchInterface
{

  /**
   *
   * @var string
   */
  private $release = '3.6.1';

  /**
   *
   * @var Array
   */
  private $concern = array(base::APPLICATION_BOX);

  /**
   *
   * @return string
   */
  function get_release()
  {
    return $this->release;
  }

  public function require_all_upgrades()
  {
    return false;
  }

  /**
   *
   * @return Array
   */
  function concern()
  {
    return $this->concern;
  }

  function apply(base &$appbox)
  {
    $Core = \bootstrap::getCore();

    $em = $Core->getEntityManager();

    $dql = 'SELECT e FROM Entities\BasketElement e';

    $query = $em->createQuery($dql);

    $count = Paginate::getTotalQueryResults($query);

    $n       = 0;
    $perPage = 100;

    while ($n < $count)
    {
      $paginateQuery = Paginate::getPaginateQuery($query, $n, $perPage);

      $result = $paginateQuery->getResult();

      foreach ($result as $basketElement)
      {
        try
        {
          $basketElement->getRecord();
        }
        catch (\Exception $e)
        {
          $em->remove($basketElement);
        }
      }

      unset($paginateQuery);
      unset($result);
      $em->flush();

      $n += $perPage;
    }

    $dql = 'SELECT b FROM Entities\Basket b';

    $query = $em->createQuery($dql);

    $count = Paginate::getTotalQueryResults($query);

    $n       = 0;
    $perPage = 100;

    while ($n < $count)
    {
      $paginateQuery = Paginate::getPaginateQuery($query, $n, $perPage);

      $result = $paginateQuery->getResult();

      foreach ($result as $basket)
      {
        $htmlDesc = $basket->getDescription();

        $description = trim(strip_tags(str_replace("<br />", "\n", $htmlDesc)));

        if ($htmlDesc == $description)
        {
          continue;
        }

        $basket->setDescription($description);
      }

      unset($paginateQuery);
      unset($result);
      $em->flush();

      $n += $perPage;
    }

    return true;
  }

}
