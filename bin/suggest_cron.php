<?php

include __DIR__ . '/../lib/bootstrap.php';
define('FREQ_THRESHOLD', 10);
define('SUGGEST_DEBUG', 0);

$registry = registry::get_instance();

function test_number($number)
{
  $datas = preg_match('/^[0-9]+$/', $number, $matches);

  return (count($matches) > 0 );
}

/// build a list of trigrams for a given keywords
function BuildTrigrams($keyword)
{
  $t = "__" . $keyword . "__";

  $trigrams = "";
  for ($i = 0; $i < strlen($t) - 2; $i++)
    $trigrams .= substr($t, $i, 3) . " ";

  return $trigrams;
}

function BuildDictionarySQL($in)
{
  $out = '';


  $n = 0;
  $lines = explode("\n", $in);
  foreach ($lines as $line)
  {
    if (trim($line) === '')
      continue;
    list ( $keyword, $freq ) = split(" ", trim($line));

    if ($freq < FREQ_THRESHOLD || strstr($keyword, "_") !== false || strstr($keyword, "'") !== false)
      continue;

    if (test_number($keyword))
    {
      echo "dismiss number keyword    : $keyword\n";
      continue;
    }
    if (mb_strlen($keyword) < 3)
    {
      echo "dismiss too short keyword : $keyword \n";
      continue;
    }

    $trigrams = BuildTrigrams($keyword);

    if ($n++)
      $out .= ",\n";
    $out .= "( $n, '$keyword', '$trigrams', $freq )";
  }

  if (trim($out) !== '')
  {
    $out = "INSERT INTO suggest VALUES " . $out . ";";
  }

  return $out;
}

$params = phrasea::sbas_params();

foreach ($params as $sbas_id => $p)
{
  $index = crc32(str_replace(array('.', '%'), '_', sprintf('%s_%s_%s_%s', $p['host'], $p['port'], $p['user'], $p['dbname'])));
  $tmp_file = $registry->get('GV_RootPath') . 'tmp/dict' . $index . '.txt';

  echo "process $index " . $sbas_id . " \n";

  $cmd = '/usr/local/bin/indexer metadatas' . $index . '  --buildstops ' . $tmp_file . ' 1000000 --buildfreqs';
  exec($cmd);

  try
  {
    $connbas = connection::getPDOConnection($sbas_id);
  }
  catch (Exception $e)
  {
    continue;
  }
  $sql = 'TRUNCATE suggest';
  $stmt = $connbas->prepare($sql);
  $stmt->execute();
  $stmt->closeCursor();

  $sql = BuildDictionarySQL(file_get_contents($tmp_file));

  if (trim($sql) !== '')
  {
    $stmt = $connbas->prepare($sql);
    $stmt->execute();
    $stmt->closeCursor();
  }
  unlink($tmp_file);
}
