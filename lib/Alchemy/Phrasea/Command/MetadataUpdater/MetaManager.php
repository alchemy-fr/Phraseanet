<?php

namespace Alchemy\Phrasea\Command\MetadataUpdater;

use Monolog\Logger;
use PHPExiftool\Reader;

class MetaManager
{

  public function get_meta($file,$tag )
  {
       $logger = new Logger('exif-tool');
       $reader = Reader::create($logger);

       $metadatas = $reader->files($file)->first();
       $value = NULL;

       $metadata = null;

       foreach ($metadatas as $metadata) {
          if ($metadata->getTag() == $tag) {
               $value = explode(";", $metadata->getValue());
               break;
          }
       }

      return $value;
  }

  public function setMetaByAPI($host,$base,$record,$token,$values,$metastructid)
  {
      $url = $host."/api/v1/records/".$base."/".$record."/setmetadatas/?oauth_token=".$token;

      $value="";
      $opt = [];
      foreach ($values as $key => $value) {
          $opt[] = "metadatas[".$key."][meta_struct_id]=".$metastructid."&metadatas[".$key."][meta_id]=&metadatas[".$key."][value]=".trim($value);
      }

      $opts = implode("&", $opt);

      echo $url."\n";
      echo $opts."\n";

       $ch = curl_init();
       curl_setopt($ch, CURLOPT_POST, 1);
       curl_setopt($ch, CURLOPT_URL, $url);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
       curl_setopt($ch, CURLOPT_POSTFIELDS, $opts);

       $ce = curl_exec($ch);
       curl_close($ch);

       print_r($ce);
  }
  
}
