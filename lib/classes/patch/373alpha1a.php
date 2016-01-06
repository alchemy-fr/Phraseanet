<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

class patch_373alpha1a extends patchAbstract
{
    /** @var string */
    private $release = '3.7.3-alpha.1';

    /** @var array */
    private $concern = [base::APPLICATION_BOX];

    /**
     * {@inheritdoc}
     */
    public function get_release()
    {
        return $this->release;
    }

    /**
     * {@inheritdoc}
     */
    public function require_all_upgrades()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function concern()
    {
        return $this->concern;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        $sql = 'SELECT * FROM registry
                WHERE `key` = :key';
        $stmt = $app->getApplicationBox()->get_connection()->prepare($sql);

        $Regbinaries = [
            'GV_cli',
            'GV_swf_extract',
            'GV_pdf2swf',
            'GV_swf_render',
            'GV_unoconv',
            'GV_ffmpeg',
            'GV_ffprobe',
            'GV_mp4box',
            'GV_pdftotext',
        ];

        $mapping = [
            'GV_cli'           => 'php_binary',
            'GV_swf_extract'   => 'swf_extract_binary',
            'GV_pdf2swf'       => 'pdf2swf_binary',
            'GV_swf_render'    => 'swf_render_binary',
            'GV_unoconv'       => 'unoconv_binary',
            'GV_ffmpeg'        => 'ffmpeg_binary',
            'GV_ffprobe'       => 'ffprobe_binary',
            'GV_mp4box'        => 'mp4box_binary',
            'GV_pdftotext'     => 'pdftotext_binary',
        ];

        $binaries = ['ghostscript_binary' => null];

        foreach ($Regbinaries as $name) {
            $stmt->execute([':key' => $name]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $value = is_executable($row['value']) ? $row['value'] : null;

            $binaries[$mapping[$name]] = $value;
        }

        $stmt->closeCursor();

        $config = $app['configuration.store']->getConfig();
        $config['binaries'] = $binaries;

        $sql = 'DELETE FROM registry WHERE `key` = :key';
        $stmt = $app->getApplicationBox()->get_connection()->prepare($sql);

        foreach ($Regbinaries as $name) {
            $stmt->execute([':key' => $name]);
        }

        $stmt->closeCursor();

        $sql = 'SELECT value FROM registry WHERE `key` = :key';
        $stmt = $app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute([':key'=>'GV_sit']);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $config['main']['key'] = $row['value'];

        $app['configuration.store']->setConfig($config);

        $sql = 'DELETE FROM registry WHERE `key` = :key';
        $stmt = $app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute([':key'=>'GV_sit']);
        $stmt->closeCursor();

        return true;
    }
}
