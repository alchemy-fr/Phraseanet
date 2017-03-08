<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

use Alchemy\Phrasea\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SubdefsController extends Controller
{
    /**
     * @param int $sbas_id
     * @return Response
     */
    function indexAction($sbas_id) {
        $databox = $this->findDataboxById((int) $sbas_id);
        $config = $this->getConfiguration();

        return $this->render('admin/subdefs.html.twig', [
            'databox' => $databox,
            'subdefs' => $databox->get_subdef_structure(),
            'config' => $config
        ]);
    }

    /**
     * @param Request $request
     * @param int     $sbas_id
     * @return Response
     * @throws \Exception
     */
    function changeSubdefsAction(Request $request, $sbas_id) {
        $delete_subdef = $request->request->get('delete_subdef');
        $toadd_subdef = $request->request->get('add_subdef');
        $Parmsubdefs = $request->request->get('subdefs', []);

        $databox = $this->findDataboxById((int) $sbas_id);

        $add_subdef = ['class' => null, 'name'  => null, 'group' => null];
        foreach ($add_subdef as $k => $v) {
            if (!isset($toadd_subdef[$k]) || trim($toadd_subdef[$k]) === '') {
                unset($add_subdef[$k]);
            } else {
                $add_subdef[$k] = $toadd_subdef[$k];
            }
        }

        if ($delete_subdef) {
            $delete_subef = explode('_', $delete_subdef, 2);
            $group = $delete_subef[0];
            $name = $delete_subef[1];
            $subdefs = $databox->get_subdef_structure();
            $subdefs->delete_subdef($group, $name);
        } elseif (count($add_subdef) === 3) {
            $subdefs = $databox->get_subdef_structure();

            $group = $add_subdef['group'];
            /** @var \unicode $unicode */
            $unicode = $this->app['unicode'];
            $name = $unicode->remove_nonazAZ09($add_subdef['name'], false);
            $class = $add_subdef['class'];

            $subdefs->add_subdef($group, $name, $class);
        } else {
            $subdefs = $databox->get_subdef_structure();

            foreach ($Parmsubdefs as $post_sub) {
                $options = [];

                $post_sub_ex = explode('_', $post_sub, 2);

                $group = $post_sub_ex[0];
                $name = $post_sub_ex[1];

                $class = $request->request->get($post_sub . '_class');
                $downloadable = $request->request->get($post_sub . '_downloadable');

                $defaults = ['path', 'meta', 'mediatype'];

                foreach ($defaults as $def) {
                    $parm_loc = $request->request->get($post_sub . '_' . $def);

                    if ($def == 'meta' && !$parm_loc) {
                        $parm_loc = "no";
                    }

                    $options[$def] = $parm_loc;
                }

                $mediatype = $request->request->get($post_sub . '_mediatype');
                $media = $request->request->get($post_sub . '_' . $mediatype, []);

                foreach ($media as $option => $value) {

                    if ($option == 'resolution' && $mediatype == 'image') {
                        $option = 'dpi';
                    }

                    $options[$option] = $value;
                }

                $labels = $request->request->get($post_sub . '_label', []);

                $subdefs->set_subdef($group, $name, $class, $downloadable, $options, $labels);
            }
        }

        return $this->app->redirectPath('admin_subdefs_subdef', [
            'sbas_id' => $databox->get_sbas_id(),
        ]);
    }

    /**
     * @return array
     */
    protected function getConfiguration()
    {
        $config = array(
            "image" => array(
                "definitions" => array(
                    "JPG" => null,
                    "160px JPG" => array("160", "75", "yes", "yes", "75", "jpeg", ["all"]),
                    "320 px JPG (thumbnail Phraseanet)" => array("320", "75", "yes", "yes", "75", "jpeg", ["all"]),
                    "640px JPG" => array("640", "75", "yes", "yes", "75", "jpeg", ["all"]),
                    "1280px JPG (preview Phraseanet)" => array("1280", "75", "yes", "yes", "75", "jpeg", ["all"]),
                    "2560px JPG" => array("2560", "75", "yes", "yes", "75", "jpeg", ["all"]),
                    "PNG" => null,
                    "160px PNG 8 bits" => array("160", "75", "yes", "yes", "75", "png", ["all"]),
                    "320px PNG 8 bits" => array("320", "75", "yes", "yes", "75", "png", ["all"]),
                    "640px PNG 8 bits" => array("640", "75", "yes", "yes", "75", "png", ["all"]),
                    "1280px PNG 8 bits" => array("1280", "75", "yes", "yes", "75", "png", ["all"]),
                    "2560px PNG 8 bits" => array("2560", "75", "yes", "yes", "75", "png", ["all"]),
                    "TIFF" => null,
                    "1280 TIFF" => array("1280", "75", "yes", "yes", "75", "tiff", ["all"]),
                    "2560px TIFF" => array("2560", "75", "yes", "yes", "75", "tiff", ["all"]),
                ),
                "form" => array(
                    "size" => "slide",
                    "resolution" => "slide",
                    "strip" => "radio",
                    "flatten" => "radio",
                    "quality" => "slide",
                    "icodec" => "select",
                    "devices" => "checkbox",
                ),
            ),
            "video" => array(
                "definitions" => array(
                    "video codec H264" => null,
                    "144P H264 128 kbps ACC 128kbps" => array(
                        "128",
                        "44100",
                        "256",
                        "25",
                        "128",
                        "25",
                        "libx264",
                        "libfaac",
                        ["all"]
                    ),
                    "240P H264 256 kbps ACC 128kbps" => array(
                        "128",
                        "44100",
                        "426",
                        "25",
                        "256",
                        "25",
                        "libx264",
                        "libfaac",
                        ["all"]
                    ),
                    "360P H264 576 kbps ACC 128kbps" => array(
                        "128",
                        "44100",
                        "480",
                        "25",
                        "576",
                        "25",
                        "libtheora",
                        "libfaac",
                        ["all"]
                    ),
                    "480P H264 750 kbps ACC 128kbps" => array(
                        "128",
                        "44100",
                        "854",
                        "25",
                        "750",
                        "25",
                        "libx264",
                        "libfaac",
                        ["all"]
                    ),
                    "720P H264 1492 kbps ACC 128kbps" => array(
                        "128",
                        "44100",
                        "1280",
                        "25",
                        "1492",
                        "25",
                        "libx264",
                        "libfaac",
                        ["all"]
                    ),
                    "1080P H264 2420 kbps ACC 128kbps" => array(
                        "128",
                        "44100",
                        "1920",
                        "25",
                        "2420",
                        "25",
                        "libx264",
                        "libfaac",
                        ["all"]
                    ),
                    "video codec libvpx" => null,
                    "144P webm 128 kbps ACC 128kbps" => array(
                        "128",
                        "44100",
                        "256",
                        "25",
                        "128",
                        "25",
                        "libvpx",
                        "libfaac",
                        ["all"]
                    ),
                    "240P webm 256 kbps ACC 128kbps" => array(
                        "128",
                        "44100",
                        "426",
                        "25",
                        "256",
                        "25",
                        "libvpx",
                        "libfaac",
                        ["all"]
                    ),
                    "360P webm 576 kbps ACC 128kbps" => array(
                        "128",
                        "44100",
                        "480",
                        "25",
                        "576",
                        "25",
                        "libvpx",
                        "libfaac",
                        ["all"]
                    ),
                    "480P webm 750 kbps ACC 128kbps" => array(
                        "128",
                        "44100",
                        "854",
                        "25",
                        "750",
                        "25",
                        "libvpx",
                        "libfaac",
                        ["all"]
                    ),
                    "720P webm 1492 kbps ACC 128kbps" => array(
                        "128",
                        "44100",
                        "1280",
                        "25",
                        "1492",
                        "25",
                        "libvpx",
                        "libfaac",
                        ["all"]
                    ),
                    "1080P webm 2420 kbps ACC 128kbps" => array(
                        "128",
                        "44100",
                        "1920",
                        "25",
                        "2420",
                        "25",
                        "libvpx",
                        "libfaac",
                        ["all"]
                    ),
                ),
                "form" => array(
                    "audiobitrate" => "slide",
                    "audiosamplerate" => "select",
                    "bitrate" => "slide",
                    "GOPsize" => "slide",
                    "size" => "slide",
                    "fps" => "slide",
                    "vcodec" => "select",
                    "acodec" => "select",
                    "devices" => "checkbox",
                ),
            ),
            "gif" => array(
                "definitions" => array(
                    "256 px fast 200 ms" => array("256", "200", ["all"]),
                    "256 px very fast 120 ms" => array("256", "120", ["all"]),
                    "320 px fast 200 ms" => array("320", "200", ["all"]),
                ),
                "form" => array(
                    "size" => "slide",
                    "delay" => "slide",
                    "devices" => "checkbox",
                ),
            ),
            "audio" => array(
                "definitions" => array(
                    "Low AAC 96 kbit/s" => array("100", "8000", "libmp3lame", ["all"]),
                    "Normal AAC 128 kbit/s" => array("180", "44100", "libmp3lame", ["all"]),
                    "High AAC 320 kbit/s" => array("230", "50000", "libmp3lame", ["all"]),
                ),
                "form" => array(
                    "audiobitrate" => "slide",
                    "audiosamplerate" => "select",
                    "acodec" => "select",
                    "devices" => "checkbox",
                ),
            ),
            "flexpaper" => array(
                "definitions" => array(
                    "low_F" => array(),
                    "medium_F" => array(),
                    "high_F" => array(),
                ),
                "form" => array(),
            ),
        );

        return $config;
    }
}
