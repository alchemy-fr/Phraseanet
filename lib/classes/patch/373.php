<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class patch_373 implements patchInterface
{
    /**
     *
     * @var string
     */
    private $release = '3.7.3';

    /**
     *
     * @var Array
     */
    private $concern = array(base::APPLICATION_BOX);

    /**
     *
     * @return string
     */
    public function get_release()
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
    public function concern()
    {
        return $this->concern;
    }

    /**
     * @param base $databox
     */
    public function apply(base $appbox, Application $app)
    {
        $sql = 'SELECT * FROM registry WHERE `key` = :key';
        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);

        $Regbinaries = array(
            'GV_cli',
            'GV_imagick',
            'GV_pathcomposite',
            'GV_swf_extract',
            'GV_pdf2swf',
            'GV_swf_render',
            'GV_unoconv',
            'GV_ffmpeg',
            'GV_ffprobe',
            'GV_mp4box',
            'GV_pdftotext',
        );

        $mapping = array(
            'GV_cli'           => 'php_binary',
            'GV_imagick'       => 'convert_binary',
            'GV_pathcomposite' => 'composite_binary',
            'GV_swf_extract'   => 'swf_extract_binary',
            'GV_pdf2swf'       => 'pdf2swf_binary',
            'GV_swf_render'    => 'swf_render_binary',
            'GV_unoconv'       => 'unoconv_binary',
            'GV_ffmpeg'        => 'ffmpeg_binary',
            'GV_ffprobe'       => 'ffprobe_binary',
            'GV_mp4box'        => 'mp4box_binary',
            'GV_pdftotext'     => 'pdftotext_binary',
        );

        $binaries = array('ghostscript_binary' => '');

        foreach ($Regbinaries as $name) {
            $stmt->execute(array(':key' => $name));
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $value = is_executable($row['value']) ? $row['value'] : '';

            $binaries[$mapping[$name]] = $value;
        }

        $stmt->closeCursor();

        $app['phraseanet.configuration']->setBinaries(array('binaries' => $binaries));

        $sql = 'DELETE FROM registry WHERE `key` = :key';
        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);

        foreach ($Regbinaries as $name) {
            $stmt->execute(array(':key' => $name));
        }

        $stmt->closeCursor();

        $sql = 'SELECT value FROM registry WHERE `key` = :key';
        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':key'=>'GV_sit'));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $configs = $app['phraseanet.configuration']->getConfigurations();
        $configs['key'] = $row['value'];
        $app['phraseanet.configuration']->setConfigurations($configs);

        $sql = 'DELETE FROM registry WHERE `key` = :key';
        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':key'=>'GV_sit'));
        $stmt->closeCursor();

        return true;
    }
}
