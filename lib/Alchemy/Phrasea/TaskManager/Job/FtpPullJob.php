<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\TaskManager\Editor\FtpPullEditor;
use Alchemy\Phrasea\Exception\RuntimeException;

class FtpPullJob extends AbstractJob
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->translator->trans("task::ftp:FTP Pull");
    }

    /**
     * {@inheritdoc}
     */
    public function getJobId()
    {
        return 'FtpPull';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->translator->trans('Periodically fetches an FTP repository content locally');
    }

    /**
     * {@inheritdoc}
     */
    public function getEditor()
    {
        return new FtpPullEditor($this->translator);
    }

    /**
     * {@inheritdoc}
     */
    protected function doJob(JobData $data)
    {
        $app = $data->getApplication();
        $settings = simplexml_load_string($data->getTask()->getSettings());

        $proxy = (string) $settings->proxy;
        $proxyport = (string) $settings->proxyport;
        $localPath = (string) $settings->localpath;
        $ftpPath = (string) $settings->ftppath;
        $host = (string) $settings->host;
        $port = (string) $settings->port;
        $user = (string) $settings->user;
        $password = (string) $settings->password;
        $ssl = (Boolean) (string) $settings->ssl;
        $passive = (Boolean) (string) $settings->passive;

        foreach ([
            'localpath' => $localPath,
            'host'      => $host,
            'port'      => $host,
            'user'      => $user,
            'password'  => $password,
            'ftppath'   => $ftpPath,
            ] as $name => $value) {
            if (trim($value) === '') {
                // maybe throw an exception to consider the job as failing ?
                $this->log('error', sprintf('setting `%s` must be set', $name));
                throw new RuntimeException(sprintf('`%s` setting is empty', $name));
            }
        }

        $app['filesystem']->mkdir($localPath, 0750);

        if (!is_dir($localPath)) {
            $this->log('error', sprintf('`%s` does not exists', $localPath));
            throw new RuntimeException(sprintf('`%s` does not exists', $localPath));
        }
        if (!is_writeable($localPath)) {
            $this->log('error', sprintf('`%s` is not writeable', $localPath));
            throw new RuntimeException(sprintf('`%s` is not writeable', $localPath));
        }

        $ftp = $app['phraseanet.ftp.client']($host, $port, 90, $ssl, $proxy, $proxyport);
        $ftp->login($user, $password);
        $ftp->passive($passive);
        $ftp->chdir($ftpPath);
        $list_1 = $ftp->list_directory(true);

        $done = 0;
        $this->log('debug', "attente de 25sec pour avoir les fichiers froids...");
        $this->pause(25);
        if (!$this->isStarted()) {
            $ftp->close();
            $this->log('debug', "Stopping");

            return;
        }

        $list_2 = $ftp->list_directory(true);

        foreach ($list_1 as $filepath => $timestamp) {
            $done++;
            if (!isset($list_2[$filepath])) {
                $this->log('debug', "le fichier $filepath a disparu...\n");
                continue;
            }
            if ($list_2[$filepath] !== $timestamp) {
                $this->log('debug', "le fichier $filepath a ete modifie depuis le dernier passage...");
                continue;
            }

            $finalpath = \p4string::addEndSlash($localPath) . ($filepath[0] == '/' ? mb_substr($filepath, 1) : $filepath);
            $this->log('debug', "Rappatriement de $filepath vers $finalpath\n");

            if (file_exists($finalpath)) {
                $this->log('debug', "Un fichier du meme nom ($finalpath) existe deja, skipping");
                continue;
            }

            $this->log('debug', "Create ".dirname($finalpath)."");
            $app['filesystem']->mkdir(dirname($finalpath), 0750);

            $this->log('debug', "Get $filepath to $finalpath");
            $ftp->get($finalpath, $filepath);
            $this->log('debug', "Remove $filepath");
            $ftp->delete($filepath);
        }

        $ftp->close();
    }
}
