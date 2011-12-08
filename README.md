Phraseanet 3.5 - Digital Asset Management application
=================================================

#Features :

Metadatas Management (include Thesaurus and DublinCore Mapping)
Search Engine (Sphinx Search Integration)
RestFull APIS (See Developer Documentation http://docs.phraseanet.com/Devel)
Bridge to Youtube/Dailymotion/Flickr

#Documentation :

http://docs.phraseanet.com

#Easy Installation

**Fetch Sources**

<pre>

git clone git://github.com/alchemy-fr/Phraseanet.git Phraseanet
cd Phraseanet
./vendors.php

</pre>


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