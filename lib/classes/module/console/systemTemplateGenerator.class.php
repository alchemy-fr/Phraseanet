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

class module_console_systemTemplateGenerator extends Command
{

  public function __construct($name = null)
  {
    parent::__construct($name);

    $this->setDescription('Generate template files');

    return $this;
  }

  public function execute(InputInterface $input, OutputInterface $output)
  {
    $tplDir = __DIR__ . '/../../../../templates/';
    $tmpDir = __DIR__ . '/../../../../tmp/cache_twig/';
    
    $loader = new Twig_Loader_Filesystem($tplDir);

    $twig = new Twig_Environment($loader, array(
                'cache' => $tmpDir,
                'auto_reload' => true
            ));
    $twig->addExtension(new Twig_Extensions_Extension_I18n());

    /**
     * @todo clean all duplicate filters
     */
    $twig->addFilter('serialize', new Twig_Filter_Function('serialize'));
    $twig->addFilter('sbas_names', new Twig_Filter_Function('phrasea::sbas_names'));
    $twig->addFilter('sbas_name', new Twig_Filter_Function('phrasea::sbas_names'));
    $twig->addFilter('unite', new Twig_Filter_Function('p4string::format_octets'));
    $twig->addFilter('stristr', new Twig_Filter_Function('stristr'));
    $twig->addFilter('implode', new Twig_Filter_Function('implode'));
    $twig->addFilter('stripdoublequotes', new Twig_Filter_Function('stripdoublequotes'));
    $twig->addFilter('phraseadate', new Twig_Filter_Function('phraseadate::getPrettyString'));
    $twig->addFilter('format_octets', new Twig_Filter_Function('p4string::format_octets'));
    $twig->addFilter('geoname_display', new Twig_Filter_Function('geonames::name_from_id'));
    $twig->addFilter('get_collection_logo', new Twig_Filter_Function('collection::getLogo'));
    $twig->addFilter('nl2br', new Twig_Filter_Function('nl2br'));
    $twig->addFilter('floor', new Twig_Filter_Function('floor'));
    $twig->addFilter('bas_name', new Twig_Filter_Function('phrasea::bas_names'));
    $twig->addFilter('bas_names', new Twig_Filter_Function('phrasea::bas_names'));
    $twig->addFilter('basnames', new Twig_Filter_Function('phrasea::bas_names'));
    $twig->addFilter('urlencode', new Twig_Filter_Function('urlencode'));
    $twig->addFilter('sbasFromBas', new Twig_Filter_Function('phrasea::sbasFromBas'));
    $twig->addFilter('str_replace', new Twig_Filter_Function('str_replace'));
    $twig->addFilter('strval', new Twig_Filter_Function('strval'));
    $twig->addFilter('key_exists', new Twig_Filter_Function('array_key_exists'));
    $twig->addFilter('array_keys', new Twig_Filter_Function('array_keys'));
    $twig->addFilter('round', new Twig_Filter_Function('round'));
    $twig->addFilter('get_class', new Twig_Filter_Function('get_class'));
    $twig->addFilter('formatdate', new Twig_Filter_Function('phraseadate::getDate'));
    $twig->addFilter('getPrettyDate', new Twig_Filter_Function('phraseadate::getPrettyString'));
    $twig->addFilter('prettyDate', new Twig_Filter_Function('phraseadate::getPrettyString'));
    $twig->addFilter('prettyString', new Twig_Filter_Function('phraseadate::getPrettyString'));
    $twig->addFilter('formatoctet', new Twig_Filter_Function('p4string::format_octet'));
    $twig->addFilter('getDate', new Twig_Filter_Function('phraseadate::getDate'));
    $twig->addFilter('geoname_name_from_id', new Twig_Filter_Function('geonames::name_from_id'));


    $n_ok = $n_error = 0;

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tplDir), RecursiveIteratorIterator::LEAVES_ONLY) as $file)
    {
      if (strpos($file, '/.svn/') !== false)
        continue;
      if (substr($file->getFilename(), 0, 1) === '.')
        continue;

      try
      {
        $twig->loadTemplate(str_replace($tplDir, '', $file));
        $output->writeln('' . $file . '');
        $n_ok++;
      }
      catch (Exception $e)
      {
        $output->writeln('<error>' . $e->getMessage() . '</error>');
        $n_error++;
      }
    }

    $output->writeln("");
    $output->write(sprintf('%d templates generated. ', $n_ok));

    if ($n_error > 0)
    {
      $output->write(sprintf('<error>%d templates failed.</error>', $n_error));
    }

    $output->writeln("");

    return $n_error;
  }

}
