<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * UpgradeManager for Phraseanet.
 * Datas are written in a lock file.
 * If lock file exists, it contains YAML datas about the current process.
 *
 * @package     Setup
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Setup_Upgrade
{

  /**
   *
   * @var appbox
   */
  protected $appbox;

  /**
   *
   * @var string
   */
  protected $message;

  /**
   *
   * @var int
   */
  protected $total_steps = 0;

  /**
   *
   * @var int
   */
  protected $completed_steps = 0;

  /**
   *
   * @param appbox $appbox
   * @return Setup_Upgrade
   */
  public function __construct(appbox &$appbox)
  {
    if (self::lock_exists())
    {
      throw new Exception_Setup_UpgradeAlreadyStarted('The upgrade is already started');
    }
    
    $this->appbox = $appbox;
    
    if(count(User_Adapter::get_wrong_email_users($appbox)) > 0)
    {
      throw new Exception_Setup_FixBadEmailAddresses('Please fix the database before starting');
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
   * Add steps to do to the counter
   *
   * @param int $how_many
   * @return Setup_Upgrade
   */
  public function add_steps($how_many)
  {
    $this->total_steps += (int) $how_many;
    $this->write_lock();

    return $this;
  }

  /**
   * Add completed steps to the counter
   *
   * @param int $how_many
   * @return Setup_Upgrade
   */
  public function add_steps_complete($how_many)
  {
    $this->completed_steps += (int) $how_many;
    $this->write_lock();

    return $this;
  }

  /**
   * Set the current message
   *
   * @param string $message
   * @return Setup_Upgrade
   */
  public function set_current_message($message)
  {
    $this->message = $message;
    $this->write_lock();

    return $this;
  }

  /**
   *
   * @return float
   */
  protected function get_percentage()
  {
    if ($this->total_steps === 0)

      return 1;
    return round(max(min(($this->completed_steps / $this->total_steps), 1), 0), 2);
  }

  /**
   *
   *
   * @return Setup_Upgrade
   */
  protected function write_lock()
  {
    $date_obj = new DateTime();
    $dumper = new Symfony\Component\Yaml\Dumper();
    $datas = $dumper->dump(
            array(
                'percentage' => $this->get_percentage()
                , 'total_steps' => $this->total_steps
                , 'completed_steps' => $this->completed_steps
                , 'message' => $this->message
                , 'last_update' => $date_obj->format(DATE_ATOM)
            ),1
    );

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
  protected static function lock_exists()
  {
    clearstatcache();

    return file_exists(self::get_lock_file());
  }

  /**
   * Return the path fil to the lock file
   *
   * @return string
   */
  public static function get_lock_file()
  {
    return __DIR__ . '/../../../tmp/upgrade.lock';
  }

  /**
   *
   * @return Void
   */
  protected static function remove_lock_file()
  {
    if (self::lock_exists())
      unlink(self::get_lock_file());

    return;
  }

  /**
   *
   * Returns an array containing datas about the Upgrade Status.
   * Contains the following keys :
   *  - active          : (booolean) tells if there's a current upgrade
   *  - percentage      : (float) a number between 0 and 1 of the current progress
   *  - total_steps     : (int) total steps
   *  - completed_steps : (int) current complete steps
   *  - message         : (string) a message
   *  - last_update     : (string) last update in ATOM format
   *
   *
   * @return Array
   */
  public static function get_status()
  {
    $active = self::lock_exists();

    $datas = array(
        'active' => $active
        , 'percentage' => 1
        , 'total_steps' => 0
        , 'completed_steps' => 0
        , 'message' => null
        , 'last_update' => null
    );

    if ($active)
    {
      $parser = new Symfony\Component\Yaml\Parser();
      $datas = array_merge(
              $datas
              , $parser->parse(file_get_contents(self::get_lock_file()))
      );
    }

    return $datas;
  }

}
