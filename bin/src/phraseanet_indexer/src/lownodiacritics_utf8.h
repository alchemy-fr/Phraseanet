#ifndef LOWNODIACRITICS_INCLUDED
#define LOWNODIACRITICS_INCLUDED 1

#define CFLAG_NORMALCHAR 0
#define CFLAG_ENDCHAR 1
#define CFLAG_SPACECHAR 2

typedef struct { unsigned char c;  unsigned char flags; } CMAP1;
typedef struct { unsigned char *s; unsigned char flags; } CMAP2;


#endif
