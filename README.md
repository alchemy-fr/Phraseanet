Phraseanet 3.6 - Digital Asset Management application
=================================================

[![Build Status](https://secure.travis-ci.org/alchemy-fr/Phraseanet.png?branch=master)](http://travis-ci.org/alchemy-fr/Phraseanet)

#Features :

Metadatas Management (include Thesaurus and DublinCore Mapping)
Search Engine (Sphinx Search Integration)
RestFull APIS (See Developer Documentation https://docs.phraseanet.com/3.6/Devel)
Bridge to Youtube/Dailymotion/Flickr

#Documentation :

https://docs.phraseanet.com/3.6/

#Easy Installation

Get the latests sources here https://github.com/alchemy-fr/Phraseanet/downloads

**Setup your webserver**

***Nginx***
<pre>
server {
  listen       80;
  server_name  subdeomain.domain.tld;
  root         /path/to/Phraseanet/www;

  index  index.php;


  location /web {
    alias /path/to/Phraseanet/datas/web;
  }
  location /download {
    internal;
    alias /path/to/Phraseanet/tmp/download;
  }
  location /lazaret {
    internal;
    alias /path/to/Phraseanet/tmp/lazaret;
  }
}
</pre>


Let's go !

#License

Phraseanet is licensed under GPL-v3 license.

[1]: http://developer.phraseanet.com/
