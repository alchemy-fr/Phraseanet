#include "base_header.h"
#include "phrasea_clock_t.h"

#ifndef COMPILE_DL_PHRASEA2
#error COMPILE_DL_PHRASEA2 is false

#endif

# ifdef PHP_WIN32

void startChrono(CHRONO &chrono)
{
	chrono = GetTickCount();
}
double stopChrono(CHRONO &chrono)
{
	return((double)(GetTickCount()-chrono) / 1000.0);
}


# else

void startChrono(CHRONO &chrono)
{
	gettimeofday(&chrono, NULL);
	return;
}
double stopChrono(CHRONO &chrono)
{
	struct timeval t;
	gettimeofday(&t, NULL);
	t.tv_sec  -= chrono.tv_sec;
	t.tv_usec -= chrono.tv_usec;
	if(t.tv_usec < 0)
	{
		t.tv_sec--;
		t.tv_usec += 1000000;
	}
	return((double)(t.tv_sec) + ((double)(t.tv_usec))/1000000);
}

#endif
