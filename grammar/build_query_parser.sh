#!/bin/sh

jison_version="master";
jison_php="jison-$jison_version/ports/php/php.js"

cd grammar

if [ -f $jison_php ];
then
   echo "Skip jison download"
else
   echo "Download jison lib"
   wget https://github.com/zaach/jison/archive/$jison_version.zip
   unzip $jison_version.zip
   rm $jison_version.zip
fi

node jison-$jison_version/ports/php/php.js query.jison
mv QueryParser.php ../lib/Alchemy/Phrasea/SearchEngine/Elastic/QueryParser.php
