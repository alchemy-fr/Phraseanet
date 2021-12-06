FROM elasticsearch:2.4 

# uncomment to allow (groovy) script in search/sort etc.
# RUN echo "script.engine.groovy.inline.search: on" >> config/elasticsearch.yml

RUN /usr/share/elasticsearch/bin/plugin install analysis-icu
