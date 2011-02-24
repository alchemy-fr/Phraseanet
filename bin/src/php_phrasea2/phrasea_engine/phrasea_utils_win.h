
/* inclusion de l'entte de base phrasea */
#include "base_header.h"

// trier un tableau d'entiers
void sort_int(int nint, int *tint)
{
  register int i,j,tmp;
	for(i=0; i<nint-1; i++)
	{
		for(j=i+1; j<nint; j++)
		{
			if(tint[j] < tint[i])
			{
				tmp = tint[i];
				tint[i] = tint[j];
				tint[j] = tmp;
			}
		}
	}
}


# ifdef PHP_WIN32
int millisec_diff(struct _timeb *timestart, struct _timeb *timeend)
{
	return((1000 * (timeend->time - timestart->time)) + (timeend->millitm - timestart->millitm));
}
#endif