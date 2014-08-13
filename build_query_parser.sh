#!/bin/sh
cd grammar
node jison/ports/php/php.js query.jison
mv QueryParser.php ../lib/Alchemy/Phrasea/SearchEngine/Elastic/QueryParser.php