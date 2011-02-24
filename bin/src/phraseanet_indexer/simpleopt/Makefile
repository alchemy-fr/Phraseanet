CC=g++
CFLAGS=-Wall
CPPFLAGS=-Wall

OBJS=fullSample.o basicSample.o globSample.o

all: $(OBJS)
	$(CC) -o globSample  globSample.o
	$(CC) -o basicSample basicSample.o
	$(CC) -o fullSample  fullSample.o

clean:
	rm -f core *.o fullSample basicSample globSample

globSample.o: SimpleOpt.h SimpleGlob.h
fullSample.o: SimpleOpt.h SimpleGlob.h
basicSample.o: SimpleOpt.h SimpleGlob.h
