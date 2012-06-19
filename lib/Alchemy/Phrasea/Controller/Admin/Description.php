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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;

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

                $term = trim(strtolower($request->get('term')));
                $res = array();

                if ($term) {
                    $provider = new \PHPExiftool\Driver\TagProvider();

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

                return new \Symfony\Component\HttpFoundation\JsonResponse($res);
            });

        $controllers->post('/{sbas_id}/', function(Application $app, $sbas_id) {
                $Core = $app['Core'];
                $user = $Core->getAuthenticatedUser();

                $request = $app['request'];

                if ( ! $user->ACL()->has_right_on_sbas($sbas_id, 'bas_modify_struct')) {
                    throw new \Exception_Forbidden('You are not allowed to access this zone');
                }

                $databox = \databox::get_instance((int) $sbas_id);
                $fields = $databox->get_meta_structure();
                $available_dc_fields = $databox->get_available_dcfields();

                $databox->get_connection()->beginTransaction();
                $error = false;
                try {
                    if (is_array($request->get('field_ids'))) {
                        foreach ($request->get('field_ids') as $id) {
                            try {
                                $field = \databox_field::get_instance($databox, $id);
                                $field->set_name($request->get('name_' . $id));
                                $field->set_thumbtitle($request->get('thumbtitle_' . $id));
                                $field->set_tag(\databox_field::loadClassFromTagName($request->get('src_' . $id)));
                                $field->set_multi($request->get('multi_' . $id));
                                $field->set_business($request->get('business_' . $id));
                                $field->set_indexable($request->get('indexable_' . $id));
                                $field->set_required($request->get('required_' . $id));
                                $field->set_separator($request->get('separator_' . $id));
                                $field->set_readonly($request->get('readonly_' . $id));
                                $field->set_type($request->get('type_' . $id));
                                $field->set_tbranch($request->get('tbranch_' . $id));
                                $field->set_report($request->get('report_' . $id));

                                $field->setVocabularyControl(null);
                                $field->setVocabularyRestricted(false);

                                try {
                                    $vocabulary = \Alchemy\Phrasea\Vocabulary\Controller::get($request->get('vocabulary_' . $id));
                                    $field->setVocabularyControl($vocabulary);
                                    $field->setVocabularyRestricted($request->get('vocabularyrestricted_' . $id));
                                } catch (\Exception $e) {

                                }

                                $dces_element = null;

                                $class = 'databox_Field_DCES_' . $request->get('dces_' . $id);
                                if (class_exists($class))
                                    $dces_element = new $class();

                                $field->set_dces_element($dces_element);
                                $field->save();
                            } catch (\Exception $e) {
                                continue;
                            }
                        }
                    }

                    if ($request->get('newfield')) {
                        $field = \databox_field::create($databox, $request->get('newfield'));
                    }

                    if (is_array($request->get('todelete_ids'))) {
                        foreach ($request->get('todelete_ids') as $id) {
                            try {
                                $field = \databox_field::get_instance($databox, $id);
                                $field->delete();
                            } catch (\Exception $e) {

                            }
                        }
                    }
                } catch (\Exception $e) {
                    $error = true;
                }

                if ($error)
                    $databox->get_connection()->rollBack();
                else
                    $databox->get_connection()->commit();

                return new RedirectResponse('/admin/description/' . $sbas_id . '/');
            })->assert('sbas_id', '\d+');

        $controllers->get('/{sbas_id}/', function(Application $app, $sbas_id) {

                $Core = $app['Core'];
                $user = $Core->getAuthenticatedUser();

                $request = $app['request'];

                if ( ! $user->ACL()->has_right_on_sbas($sbas_id, 'bas_modify_struct')) {
                    throw new \Exception_Forbidden('You are not allowed to access this zone');
                }

                $databox = \databox::get_instance((int) $sbas_id);
                $fields = $databox->get_meta_structure();
                $available_dc_fields = $databox->get_available_dcfields();

                $params = array(
                    'databox'             => $databox,
                    'fields'              => $fields,
                    'available_dc_fields' => $available_dc_fields,
                    'vocabularies'        => \Alchemy\Phrasea\Vocabulary\Controller::getAvailable(),
                );

                return new Response($Core->getTwig()->render('admin/databox/doc_structure.twig', $params));
            })->assert('sbas_id', '\d+');

        return $controllers;
    }
}
