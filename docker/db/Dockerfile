FROM mariadb:10.4.5

RUN apt-get update && \
    apt-get install -y \
    gettext \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/

ADD ./docker/ /

WORKDIR /

ENTRYPOINT ["/phraseanet-entrypoint.sh"]
CMD ["mysqld","--sql_mode="]
