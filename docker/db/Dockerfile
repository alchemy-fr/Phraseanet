FROM mariadb:10.11.11

RUN apt-get update && \
    apt-get install -y \
    gettext \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/

ADD ./docker/ /

ENTRYPOINT ["/phraseanet-entrypoint.sh"]
CMD ["mysqld","--sql_mode="]
