<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

use Alchemy\Phrasea\Vocabulary\Controller as VocabularyController;
use PHPExiftool\Driver\TagProvider;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Description implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('/metadatas/search/', function(Application $app, Request $request) {

                $term = trim(strtolower($request->query->get('term')));
                $res = array();

                if ($term) {
                    $provider = new TagProvider();

                    $table = $provider->getLookupTable();
                    $table['phraseanet'] = array(
                        'pdftext' => array(
                            'tagname'       => 'PdfText',
                            'classname'     => '\\Alchemy\\Phrasea\\Metadata\\Tag\\PdfText',
                            'namespace'     => 'Phraseanet'),
                        'tfarchivedate' => array(
                            'tagname'   => 'TfArchivedate',
                            'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfArchivedate',
                            'namespace' => 'Phraseanet'
                        ),
                        'tfatime'   => array(
                            'tagname'   => 'TfAtime',
                            'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfAtime',
                            'namespace' => 'Phraseanet'
                        ),
                        'tfbits'    => array(
                            'tagname'    => 'TfBits',
                            'classname'  => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfBits',
                            'namespace'  => 'Phraseanet'
                        ),
                        'tfbasename' => array(
                            'tagname'    => 'TfBasename',
                            'classname'  => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfBasename',
                            'namespace'  => 'Phraseanet'),
                        'tfchannels' => array(
                            'tagname'   => 'TfChannels',
                            'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfChannels',
                            'namespace' => 'Phraseanet'
                        ),
                        'tTfCtime'  => array(
                            'tagname'    => 'TfCtime',
                            'classname'  => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfCtime',
                            'namespace'  => 'Phraseanet'
                        ),
                        'tfduration' => array(
                            'tagname'    => 'TfDuration',
                            'classname'  => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfDuration',
                            'namespace'  => 'Phraseanet'
                        ),
                        'tfeditdate' => array(
                            'tagname'     => 'TfEditdate',
                            'classname'   => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfEditdate',
                            'namespace'   => 'Phraseanet'
                        ),
                        'tfextension' => array(
                            'tagname'    => 'TfExtension',
                            'classname'  => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfExtension',
                            'namespace'  => 'Phraseanet'
                        ),
                        'tffilename' => array(
                            'tagname'    => 'TfFilename',
                            'classname'  => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfFilename',
                            'namespace'  => 'Phraseanet'
                        ),
                        'tffilepath' => array(
                            'tagname'   => 'TfFilepath',
                            'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfFilepath',
                            'namespace' => 'Phraseanet'
                        ),
                        'tfheight'  => array(
                            'tagname'    => 'TfHeight',
                            'classname'  => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfHeight',
                            'namespace'  => 'Phraseanet'
                        ),
                        'tfmimetype' => array(
                            'tagname'   => 'TfMimetype',
                            'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfMimetype',
                            'namespace' => 'Phraseanet'
                        ),
                        'tfmtime'   => array(
                            'tagname'   => 'TfMtime',
                            'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfMtime',
                            'namespace' => 'Phraseanet'
                        ),
                        'tfdirname' => array(
                            'tagname'    => 'TfDirname',
                            'classname'  => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfDirname',
                            'namespace'  => 'Phraseanet'
                        ),
                        'tfrecordid' => array(
                            'tagname'   => 'TfRecordid',
                            'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfRecordid',
                            'namespace' => 'Phraseanet'
                        ),
                        'tfsize'    => array(
                            'tagname'   => 'TfSize',
                            'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfSize',
                            'namespace' => 'Phraseanet'
                        ),
                        'tfwidth'   => array(
                            'tagname'   => 'TfWidth',
                            'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfWidth',
                            'namespace' => 'Phraseanet'
                        ),
                    );

                    foreach ($table as $namespace => $tags) {
                        $ns = strpos($namespace, $term);

                        foreach ($tags as $tagname => $datas) {
                            if ($ns === false && strpos($tagname, $term) === false) {
                                continue;
                            }

                            $res[] = array(
                                'id'    => $namespace . '/' . $tagname,
                                'label' => $datas['namespace'] . ' / ' . $datas['tagname'],
                                'value' => $datas['namespace'] . ':' . $datas['tagname'],
                            );
                        }
                    }
                }

                return $app->json($res);
            });

        $controllers->post('/{sbas_id}/', function(Application $app, Request $request, $sbas_id) {

                $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);

                $databox->get_connection()->beginTransaction();
                try {
                    if (is_array($request->request->get('field_ids'))) {
                        foreach ($request->request->get('field_ids') as $id) {
                            try {
                                $field = \databox_field::get_instance($app, $databox, $id);
                                $field->set_name($request->request->get('name_' . $id))
                                    ->set_thumbtitle($request->request->get('thumbtitle_' . $id))
                                    ->set_tag(\databox_field::loadClassFromTagName($request->request->get('src_' . $id)))
                                    ->set_business($request->request->get('business_' . $id))
                                    ->set_indexable($request->request->get('indexable_' . $id))
                                    ->set_required($request->request->get('required_' . $id))
                                    ->set_separator($request->request->get('separator_' . $id))
                                    ->set_readonly($request->request->get('readonly_' . $id))
                                    ->set_type($request->request->get('type_' . $id))
                                    ->set_tbranch($request->request->get('tbranch_' . $id))
                                    ->set_report($request->request->get('report_' . $id))
                                    ->setVocabularyControl(null)
                                    ->setVocabularyRestricted(false);

                                try {
                                    $vocabulary = VocabularyController::get($app, $request->request->get('vocabulary_' . $id));
                                    $field->setVocabularyControl($vocabulary);
                                    $field->setVocabularyRestricted($request->request->get('vocabularyrestricted_' . $id));
                                } catch (\Exception $e) {

                                }

                                $dces_element = null;

                                $class = 'databox_Field_DCES_' . $request->request->get('dces_' . $id);
                                if (class_exists($class)) {
                                    $dces_element = new $class();
                                }

                                $field->set_dces_element($dces_element)->save();
                            } catch (\Exception $e) {
                                continue;
                            }
                        }
                    }

                    if ($request->request->get('newfield')) {
                        $field = \databox_field::create($app, $databox, $request->request->get('newfield'), $request->request->get('newfield_multi'));
                    }


                    if (is_array($request->request->get('todelete_ids'))) {
                        foreach ($request->request->get('todelete_ids') as $id) {
                            try {
                                $field = \databox_field::get_instance($app, $databox, $id);
                                $field->delete();
                            } catch (\Exception $e) {

                            }
                        }
                    }

                    $databox->get_connection()->commit();
                } catch (\Exception $e) {
                    $databox->get_connection()->rollBack();
                }

                return $app->redirect('/admin/description/' . $sbas_id . '/');
            })->before(function(Request $request) use ($app) {
                if (false === $app['phraseanet.user']->ACL()
                        ->has_right_on_sbas($request->attributes->get('sbas_id'), 'bas_modify_struct')) {
                    throw new AccessDeniedHttpException('You are not allowed to access this zone');
                }
            })->assert('sbas_id', '\d+');

        $controllers->get('/{sbas_id}/', function(Application $app, $sbas_id) {

                $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);

                $params = array(
                    'databox'             => $databox,
                    'fields'              => $databox->get_meta_structure(),
                    'available_dc_fields' => $databox->get_available_dcfields(),
                    'vocabularies'        => VocabularyController::getAvailable($app),
                );

                return new Response($app['twig']->render('admin/databox/doc_structure.html.twig', $params));
            })->before(function(Request $request) use ($app) {
                if (false === $app['phraseanet.user']->ACL()
                        ->has_right_on_sbas($request->attributes->get('sbas_id'), 'bas_modify_struct')) {
                    throw new AccessDeniedHttpException('You are not allowed to access this zone');
                }
            })->assert('sbas_id', '\d+');

        return $controllers;
    }
}
