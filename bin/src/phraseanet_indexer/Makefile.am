AUTOMAKE_OPTIONS = foreign no-dependencies no-define no-dist no-installinfo

# what flags you want to pass to the C compiler & linker
CFLAGS = --pedantic -Wall -std=c99 -O2
#LDFLAGS =

# this lists the binaries to produce, the (non-PHONY, binary) targets in
# the previous manual Makefile
bin_PROGRAMS = phraseanet_indexer
phraseanet_indexer_SOURCES = src/main.cpp src/_version.h src/callback_record.cpp src/connbas.cpp src/connbas_abox.cpp src/connbas_dbox.cpp src/dom.cpp src/indexer.cpp src/lownodiacritics_utf8.cpp src/phrasea_clock_t.cpp src/sbas.cpp src/thesaurus.cpp src/trace_memory.cpp src/sockets.cpp ./xNIX/syslog_xnix.cpp 


