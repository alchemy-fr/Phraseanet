<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Subdefs
{

  /**
   *
   * @var databox
   */
  protected $databox;

  /**
   *
   * @param http_request $request
   * @param databox $databox
   */
  public function __construct(\http_request $request, \databox &$databox)
  {
    $this->databox = $databox;
    if ($request->has_post_datas())
    {
      $parm = $request->get_parms('delete_subdef', 'add_subdef', 'subdefs');

      $add_subdef = array('class' => null, 'name' => null, 'group' => null);
      foreach ($add_subdef as $k => $v)
      {
        if (!isset($parm['add_subdef'][$k]) || trim($parm['add_subdef'][$k]) === '')
          unset($add_subdef[$k]);
        else
          $add_subdef[$k] = $parm['add_subdef'][$k];
      }

      if ($parm['delete_subdef'])
      {
        $delete_subef = explode('_', $parm['delete_subdef']);
        $group = $delete_subef[0];
        $name = $delete_subef[1];

        $subdefs = $this->databox->get_subdef_structure();
        $subdefs->delete_subdef($group, $name);
      }
      elseif (count($add_subdef) === 3)
      {
        $subdefs = $this->databox->get_subdef_structure();

        $group = $add_subdef['group'];
        $name = $add_subdef['name'];
        $class = $add_subdef['class'];

        $subdefs->add_subdef($group, $name, $class);
      }
      else
      {
        $subdefs = $this->databox->get_subdef_structure();

        $options = array();

        foreach ($parm['subdefs'] as $post_sub)
        {
          $post_sub_ex = explode('_', $post_sub);
          $group = $post_sub_ex[0];
          $name = $post_sub_ex[1];

          $parm_loc = $request->get_parms($post_sub . '_class', $post_sub . '_downloadable');

          $class = $parm_loc[$post_sub . '_class'];
          $downloadable = $parm_loc[$post_sub . '_downloadable'];

          $defaults = array('path', 'baseurl', 'meta', 'mediatype');
          foreach ($defaults as $def)
          {
            $parm_loc = $request->get_parms($post_sub . '_' . $def);

            if ($def == 'meta' && !$parm_loc[$post_sub . '_' . $def])
            {
              $parm_loc[$post_sub . '_' . $def] = "no";
            }

            $options[$def] = $parm_loc[$post_sub . '_' . $def];
          }

          $parm_loc = $request->get_parms($post_sub . '_mediatype');
          $mediatype = $parm_loc[$post_sub . '_mediatype'];
          $parm_loc = $request->get_parms($post_sub . '_' . $mediatype);

          if (isset($parm_loc[$post_sub . '_' . $mediatype]))
          {
            foreach ($parm_loc[$post_sub . '_' . $mediatype] as $option => $value)
            {
              if ($option == 'resolution' && $mediatype == 'image')
                $option = 'dpi';
              $options[$option] = $value;
            }
          }
          $subdefs->set_subdef($group, $name, $class, $downloadable, $options);
        }
      }

      return \phrasea::redirect('/admin/subdefs.php?p0=' . $databox->get_sbas_id());
    }

    return $this;
  }

  public function render()
  {
    $core = \bootstrap::getCore();
    $twig = $core->getTwig();
    
    echo $twig->render(
            'admin/subdefs.twig',
            array(
                'databox' => $this->databox,
                'subdefs' => $this->databox->get_subdef_structure()
            )
    );

    return $this;
  }

}
