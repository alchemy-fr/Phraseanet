<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Setup\Version\MailChecker;
use Symfony\Component\Yaml\Dumper;

class Setup_Upgrade
{
    /**
     *
     * @var appbox
     */
    private $appbox;

    /**
     *
     * @var array
     */
    private $recommendations = [];

    public function __construct(Application $app, $force = false)
    {
        if ($force) {
            self::remove_lock_file();
        }

        if (self::lock_exists()) {
            throw new Exception_Setup_UpgradeAlreadyStarted('The upgrade is already started');
        }

        $this->appbox = $app['phraseanet.appbox'];

        if (version_compare($this->appbox->get_version(), '3.9', '<')
                && count(MailChecker::getWrongEmailUsers($app)) > 0) {
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
        self::remove_lock_file();

        return;
    }

    /**
     *
     * @param type $recommendation
     * @param type $command
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
     *
     *
     * @return Setup_Upgrade
     */
    private function write_lock()
    {
        $date_obj = new \DateTime();
        $dumper = new Dumper();
        $datas = $dumper->dump([
            'last_update'     => $date_obj->format(DATE_ATOM),
        ], 1);

        if (!file_put_contents(self::get_lock_file(), $datas))
            throw new Exception_Setup_CannotWriteLockFile(
                sprintf('Cannot write lock file to %s', self::get_lock_file())
            );

        return $this;
    }

    /**
     * Returns true if the file exists
     *
     * @return boolean
     */
    private static function lock_exists()
    {
        clearstatcache();

        return file_exists(self::get_lock_file());
    }

    /**
     * Return the path fil to the lock file
     *
     * @return string
     */
    private static function get_lock_file()
    {
        return __DIR__ . '/../../../tmp/upgrade.lock';
    }

    /**
     *
     * @return Void
     */
    private static function remove_lock_file()
    {
        if (self::lock_exists()) {
            unlink(self::get_lock_file());
        }

        return;
    }
}
