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
 * @todo write tests
 *
 * @package     KonsoleKomander
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class module_console_systemMailCheck extends Command
{

  public function __construct($name = null)
  {
    parent::__construct($name);

    $this->setDescription('Check if email addresses are unique (mandatory since 3.5)');
    $this->addOption('list'
            , 'l'
            , null
            , 'List all bad accounts instead of the interactive mode'
    );

    return $this;
  }

  public function execute(InputInterface $input, OutputInterface $output)
  {

    $appbox = appbox::get_instance();

    $output->writeln("Processing...");

    $bad_users = User_Adapter::get_wrong_email_users($appbox);

    foreach ($bad_users as $email => $users)
    {
      if ($input->getOption('list'))
      {
        $this->write_infos($email, $users, $output, $appbox);
      }
      elseif ($this->manage_group($email, $users, $output, $appbox) === false)
      {
        break;
      }

      $output->writeln("");
    }

    $output->write('Finished !', true);

    return;
  }

  protected function manage_group($email, $users, $output, $appbox)
  {
    $is_stopped = false;

    while (!$is_stopped)
    {
      $this->write_infos($email, $users, $output, $appbox);

      $dialog = $this->getHelperSet()->get('dialog');

      do
      {
        $question = '<question>What should I do ? '
                . 'continue (C), detach from mail (d), or stop (s)</question>';

        $continue = mb_strtolower($dialog->ask($output, $question, 'C'));
      }
      while (!in_array($continue, array('c', 'd', 's')));


      if ($continue == 's')
      {
        return false;
      }
      elseif ($continue == 'c')
      {
        return true;
      }
      elseif ($continue == 'd')
      {
        $dialog = $this->getHelperSet()->get('dialog');

        $id = $dialog->ask($output, '<question>Which id ?</question>', '');

        try
        {
          $tmp_user = User_Adapter::getInstance($id, $appbox);

          if ($tmp_user->get_email() != $email)
          {
            throw new Exception('Invalid user');
          }

          $tmp_user->set_email(null);

          unset($users[$id]);
        }
        catch (Exception $e)
        {
          $output->writeln('<error>Wrong id</error>');
        }
      }

      if (count($users) <= 1)
      {
        $output->writeln(sprintf("<info>\n%s fixed !</info>", $email));
        $is_stopped = true;
      }
    }

    return true;
  }

  protected function write_infos($email, $users, $output, $appbox)
  {
    $output->writeln($email);

    foreach ($users as $user)
    {
      $output->writeln(
              sprintf(
                      "\t %5d %40s   %s"
                      , $user->get_id()
                      , $user->get_display_name()
                      , $user->get_last_connection()->format('Y m d')
              )
      );
    }
  }

}
