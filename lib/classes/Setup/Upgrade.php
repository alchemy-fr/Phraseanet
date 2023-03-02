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
use Alchemy\Phrasea\Setup\Version\MailChecker;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Dumper;

class Setup_Upgrade
{
    /**
     *
     * @var Application
     */
    private $app;

    /**
     *
     * @var array
     */
    private $recommendations = [];

    public function __construct(Application $app, InputInterface $input, OutputInterface $output, $force = false)
    {
        if ($force) {
            $this->remove_lock_file();
        }

        if ($this->lock_exists()) {
            throw new Exception_Setup_UpgradeAlreadyStarted('The upgrade is already started');
        }

        $this->app = $app;

        $checker = new MailChecker($app['phraseanet.appbox']);
        if ($checker->hasWrongEmailUsers()) {
            throw new \Exception_Setup_FixBadEmailAddresses('Please fix the database before starting');
        }

        $this->write_lock();

        return $this;
    }

    /**
     *
     * @return Void
     */
    public function __destruct()
    {
        $this->remove_lock_file();

        return;
    }

    /**
     *
     * @param string $recommendation
     * @param string $command
     */
    public function addRecommendation($recommendation, $command = null)
    {
        $this->recommendations[] = [$recommendation, $command];
    }

    /**
     * Return an array of recommendations
     *
     * @return array
     */
    public function getRecommendations()
    {
        return $this->recommendations;
    }

    /**
     * @return Setup_Upgrade
     * @throws Exception_Setup_CannotWriteLockFile
     */
    private function write_lock()
    {
        $date_obj = new \DateTime();
        $dumper = new Dumper();
        $datas = $dumper->dump([
            'last_update'     => $date_obj->format(DATE_ATOM),
        ], 1);

        if (!file_put_contents($this->get_lock_file(), $datas))
            throw new Exception_Setup_CannotWriteLockFile(
                sprintf('Cannot write lock file to %s', $this->get_lock_file())
            );

        return $this;
    }

    /**
     * Returns true if the file exists
     *
     * @return boolean
     */
    private function lock_exists()
    {
        clearstatcache();

        return file_exists($this->get_lock_file());
    }

    /**
     * Return the path fil to the lock file
     *
     * @return string
     */
    private function get_lock_file()
    {
        return $this->app['tmp.path'].'/locks/upgrade.lock';
    }

    /**
     *
     * @return Void
     */
    private function remove_lock_file()
    {
        if ($this->lock_exists()) {
            unlink($this->get_lock_file());
        }

        return;
    }
}
