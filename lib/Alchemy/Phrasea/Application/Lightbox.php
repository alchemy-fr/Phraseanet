<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */

return call_user_func(
                function()
                {
                  $appbox = appbox::get_instance();

                  $session = $appbox->get_session();

                  $app = new Silex\Application();

                  $app->get('/', function () use ($session, $appbox)
                          {
                            User_Adapter::updateClientInfos((6));
                            $basket_collection = new basketCollection($appbox, $session->get_usr_id());
                            $twig = new supertwig();
                            $twig->addFilter(array('nl2br' => 'nl2br'));
                            $browser = Browser::getInstance();

                            $template = 'lightbox/index.twig';
                            if (!$browser->isNewGeneration() && !$browser->isMobile())
                              $template = 'lightbox/IE6/index.twig';

                            $output = $twig->render($template, array(
                                'baskets_collection' => $basket_collection,
                                'module_name' => 'Lightbox',
                                'module' => 'lightbox'
                                    )
                            );
                            $response = new Response($output);
                            $response->setCharset('UTF-8');

                            return $response;
                          }
                  );

                  $app->get('/ajax/NOTE_FORM/{sselcont_id}/', function($sselcont_id) use ($session, $appbox)
                          {
                            $browser = Browser::getInstance();
                            if (!$browser->isMobile())
                              return new Response('');

                            $twig = new supertwig();
                            $twig->addFilter(array('nl2br' => 'nl2br'));
                            $basket_element = basket_element_adapter::getInstance($sselcont_id);
                            $template = '/lightbox/note_form.twig';
                            $output = $twig->render($template, array('basket_element' => $basket_element, 'module_name' => ''));

                            return new Response($output);
                          }
                  )->assert('sselcont_id', '\d+');

                  $app->get('/ajax/LOAD_BASKET_ELEMENT/{sselcont_id}/', function($sselcont_id)
                          {
                            $twig = new supertwig();
                            $twig->addFilter(array('nl2br' => 'nl2br', 'formatoctet' => 'p4string::format_octets'));

                            $browser = Browser::getInstance();

                            if ($browser->isMobile())
                            {
                              $basket_element = basket_element_adapter::getInstance($sselcont_id);

                              $output = $twig->render('lightbox/basket_element.twig', array(
                                  'basket_element' => $basket_element,
                                  'module_name' => $basket_element->get_record()->get_title()
                                      )
                              );

                              return new Response($output);
                            }
                            else
                            {
                              $template_options = 'lightbox/sc_options_box.twig';
                              $template_agreement = 'lightbox/agreement_box.twig';
                              $template_selector = 'lightbox/selector_box.twig';
                              $template_note = 'lightbox/sc_note.twig';
                              $template_preview = 'common/preview.html';
                              $template_caption = 'common/caption.html';

                              if (!$browser->isNewGeneration())
                              {
                                $template_options = 'lightbox/IE6/sc_options_box.twig';
                                $template_agreement = 'lightbox/IE6/agreement_box.twig';
                              }
                              $appbox = appbox::get_instance();
                              $usr_id = $appbox->get_session()->get_usr_id();

                              $basket_element = basket_element_adapter::getInstance($sselcont_id);
                              $basket = basket_adapter::getInstance($appbox, $basket_element->get_ssel_id(), $usr_id);

                              $ret = array();
                              $ret['number'] = $basket_element->get_record()->get_number();
                              $ret['title'] = $basket_element->get_record()->get_title();

                              $ret['preview'] = $twig->render($template_preview, array('record' => $basket_element->get_record(), 'not_wrapped' => true));
                              $ret['options_html'] = $twig->render($template_options, array('basket_element' => $basket_element));
                              $ret['agreement_html'] = $twig->render($template_agreement, array('basket' => $basket, 'basket_element' => $basket_element));
                              $ret['selector_html'] = $twig->render($template_selector, array('basket_element' => $basket_element));
                              $ret['note_html'] = $twig->render($template_note, array('basket_element' => $basket_element));
                              $ret['caption'] = $twig->render($template_caption, array('view' => 'preview', 'record' => $basket_element->get_record()));
                              $output = p4string::jsonencode($ret);

                              return new Response($output, 200, array('Content-Type' => 'application/json'));
                            }
                          }
                  )->assert('sselcont_id', '\d+');




                  $app->get('/ajax/LOAD_FEED_ITEM/{entry_id}/{item_id}/', function($entry_id, $item_id)
                          {
                            $twig = new supertwig();
                            $twig->addFilter(array('nl2br' => 'nl2br', 'formatoctet' => 'p4string::format_octets'));

                            $appbox = appbox::get_instance();
                            $entry = Feed_Entry_Adapter::load_from_id($appbox, $entry_id);
                            $item = new Feed_Entry_Item($appbox, $entry, $item_id);

                            $browser = Browser::getInstance();

                            if ($browser->isMobile())
                            {
                              $output = $twig->render('lightbox/feed_element.twig', array(
                                  'feed_element' => $item,
                                  'module_name' => $item->get_record()->get_title()
                                      )
                              );

                              return new Response($output);
                            }
                            else
                            {
                              $template_options = 'lightbox/sc_options_box.twig';
                              $template_preview = 'common/preview.html';
                              $template_caption = 'common/caption.html';

                              if (!$browser->isNewGeneration())
                              {
                                $template_options = 'lightbox/IE6/sc_options_box.twig';
                              }
                              $usr_id = $appbox->get_session()->get_usr_id();

                              $ret = array();
                              $ret['number'] = $item->get_record()->get_number();
                              $ret['title'] = $item->get_record()->get_title();

                              $ret['preview'] = $twig->render($template_preview, array('record' => $item->get_record(), 'not_wrapped' => true));
                              $ret['options_html'] = $twig->render($template_options, array('basket_element' => $item));
                              $ret['caption'] = $twig->render($template_caption, array('view' => 'preview', 'record' => $item->get_record()));


                              $ret['agreement_html'] = $ret['selector_html'] = $ret['note_html'] = '';


                              $output = p4string::jsonencode($ret);

                              return new Response($output, 200, array('Content-type' => 'application/json'));
                            }
                          }
                  )->assert('entry_id', '\d+')->assert('item_id', '\d+');

                  $app->get('/validate/{ssel_id}/', function ($ssel_id) use ($session, $appbox)
                          {

                            User_Adapter::updateClientInfos((6));

                            $browser = Browser::getInstance();

                            $basket_collection = new basketCollection($appbox, $session->get_usr_id());
                            $basket = basket_adapter::getInstance($appbox, $ssel_id, $session->get_usr_id());

                            if ($basket->is_valid())
                            {
                              $basket->get_first_element()->load_users_infos();
                            }

                            $twig = new supertwig();

                            $twig->addFilter(array('nl2br' => 'nl2br'));

                            $template = 'lightbox/validate.twig';

                            if (!$browser->isNewGeneration() && !$browser->isMobile())
                              $template = 'lightbox/IE6/validate.twig';

                            $response = new Response($twig->render($template, array(
                                                'baskets_collection' => $basket_collection,
                                                'basket' => $basket,
                                                'local_title' => strip_tags($basket->get_name()),
                                                'module' => 'lightbox',
                                                'module_name' => _('admin::monitor: module validation')
                                                    )
                                    ));
                            $response->setCharset('UTF-8');

                            return $response;
                          }
                  )->assert('ssel_id', '\d+');

                  $app->get('/compare/{ssel_id}/', function ($ssel_id) use ($session, $appbox)
                          {

                            User_Adapter::updateClientInfos((6));

                            $browser = Browser::getInstance();

                            $basket_collection = new basketCollection($appbox, $session->get_usr_id());
                            $basket = basket_adapter::getInstance($appbox, $ssel_id, $session->get_usr_id());

                            if ($basket->is_valid())
                            {
                              $basket->get_first_element()->load_users_infos();
                            }

                            $twig = new supertwig();

                            $twig->addFilter(array('nl2br' => 'nl2br'));

                            $template = 'lightbox/validate.twig';

                            if (!$browser->isNewGeneration() && !$browser->isMobile())
                              $template = 'lightbox/IE6/validate.twig';

                            $response = new Response($twig->render($template, array(
                                                'baskets_collection' => $basket_collection,
                                                'basket' => $basket,
                                                'local_title' => strip_tags($basket->get_name()),
                                                'module' => 'lightbox',
                                                'module_name' => _('admin::monitor: module validation')
                                                    )
                                    ));
                            $response->setCharset('UTF-8');

                            return $response;
                          }
                  )->assert('ssel_id', '\d+');



                  $app->get('/feeds/entry/{entry_id}/', function ($entry_id) use ($session, $appbox)
                          {

                            User_Adapter::updateClientInfos((6));

                            $browser = Browser::getInstance();

                            $feed_entry = Feed_Entry_Adapter::load_from_id($appbox, $entry_id);

                            $twig = new supertwig();

                            $twig->addFilter(array('nl2br' => 'nl2br'));

                            $template = 'lightbox/feed.twig';

                            if (!$browser->isNewGeneration() && !$browser->isMobile())
                              $template = 'lightbox/IE6/feed.twig';

                            $output = $twig->render($template, array(
                                'feed_entry' => $feed_entry,
                                'first_item' => array_shift($feed_entry->get_content()),
                                'local_title' => $feed_entry->get_title(),
                                'module' => 'lightbox',
                                'module_name' => _('admin::monitor: module validation')
                                    )
                            );
                            $response = new Response($output, 200);
                            $response->setCharset('UTF-8');

                            return $response;
                          }
                  )->assert('entry_id', '\d+');

                  $app->get('/ajax/LOAD_REPORT/{ssel_id}/', function($ssel_id) use ($appbox, $app)
                          {
                            $twig = new supertwig();
                            $twig->addFilter(array('nl2br' => 'nl2br'));

                            $browser = Browser::getInstance();

                            $template = 'lightbox/basket_content_report.twig';

                            $basket = basket_adapter::getInstance($appbox, $ssel_id, $appbox->get_session()->get_usr_id());

                            $response = new Response($twig->render($template, array('basket' => $basket)));
                            $response->setCharset('UTF-8');

                            return $response;
                          }
                  )->assert('ssel_id', '\d+');

                  $app->post('/ajax/SET_NOTE/{sselcont_id}/', function ($sselcont_id) use ($app)
                          {
                            $output = array('error' => true, 'datas' => _('Erreur lors de l\'enregistrement des donnees'));
                            try
                            {
                              $request = $app['request'];
                              $note = $request->get('note');

                              $basket_element = basket_element_adapter::getInstance($sselcont_id);
                              $basket_element->set_note($note);
                              $twig = new supertwig();
                              $twig->addFilter(array('nl2br' => 'nl2br'));

                              $browser = Browser::getInstance();

                              if ($browser->isMobile())
                              {
                                $datas = $twig->render('lightbox/sc_note.twig', array('basket_element' => $basket_element));

                                $output = array('error' => false, 'datas' => $datas);
                              }
                              else
                              {
                                $template = 'lightbox/sc_note.twig';

                                $datas = $twig->render($template, array('basket_element' => $basket_element));

                                $output = array('error' => false, 'datas' => $datas);
                              }
                            }
                            catch (Exception $e)
                            {
                              return new Response('Bad Request : ' . $e->getMessage() . $e->getFile() . $e->getLine(), 400);
                            }

                            $output = p4string::jsonencode($output);

                            return new Response($output, 200, array('Content-Type' => 'application/json'));
                          }
                  )->assert('sselcont_id', '\d+');

                  $app->post('/ajax/SET_ELEMENT_AGREEMENT/{sselcont_id}/', function($sselcont_id) use ($app)
                          {
                            $request = $app['request'];
                            $agreement = (int) $request->get('agreement');

                            $ret = array(
                                'error' => true,
                                'releasable' => false,
                                'datas' => _('Erreur lors de la mise a jour des donnes ')
                            );
                            
                            try
                            {
                              $appbox = appbox::get_instance();

                              $basket_element = basket_element_adapter::getInstance($sselcont_id);
                              $basket_element->set_agreement($agreement);
                              $basket = basket_adapter::getInstance($appbox, $basket_element->get_ssel_id(), $appbox->get_session()->get_usr_id());

                              $ret = array(
                                  'error' => false
                                  , 'datas' => ''
                                  , 'releasable' => $basket->is_releasable() ? _('Do you want to send your report ?') : false
                              );
                            }
                            catch (Exception $e)
                            {
                              return new Response('Bad Request', 400);
                            }
                            $output = p4string::jsonencode($ret);

                            return new Response($output, 200, array('Content-Type' => 'application/json'));
                          }
                  )->assert('sselcont_id', '\d+');


                  $app->post('/ajax/SET_RELEASE/{ssel_id}/', function($ssel_id) use ($session, $appbox)
                          {
                            $basket = basket_adapter::getInstance($appbox, $ssel_id, $appbox->get_session()->get_usr_id());

                            $datas = array('error' => true, 'datas' => _('Erreur lors de l\'enregistrement des donnees'));
                            try
                            {
                              $appbox->get_connection()->beginTransaction();
                              $basket->set_released();
                              $datas = array('error' => false, 'datas' => _('Envoie avec succes'));
                              $appbox->get_connection()->commit();
                            }
                            catch (Exception $e)
                            {
                              $appbox->get_connection()->rollBack();

                              return new Response('Bad Request', 400);
                            }
                            $output = p4string::jsonencode($datas);

                            $response = new Response($output, 200, array('Content-Type' => 'application/json'));
                            $response->setCharset('UTF-8');

                            return $response;
                          }
                  )->assert('ssel_id', '\d+');



                  $app->error(function($e)
                          {
                            $twig = new supertwig();
                            $registry = registry::get_instance();

                            $template = 'lightbox/error.twig';

                            if ($registry->get('GV_debug'))
                            {
                              $options = array(
                                  'module' => 'validation',
                                  'module_name' => _('admin::monitor: module validation'),
                                  'error' => sprintf(
                                          '%s in %s on line %s '
                                          , $e->getMessage()
                                          , $e->getFile()
                                          , $e->getLine()
                                  )
                              );
                            }
                            else
                            {
                              $options = array(
                                  'module' => 'validation',
                                  'module_name' => _('admin::monitor: module validation'),
                                  'error' => ''
                              );
                            }
                            $output = $twig->render($template, $options);
                            $response = new Response($output, 404);
                            $response->setCharset('UTF-8');

                            return $response;
                          });

                  return $app;
                }
);
