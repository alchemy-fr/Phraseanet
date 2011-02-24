#ifndef PLATFORM_DEPENDENT_INCLUDED
#define PLATFORM_DEPENDENT_INCLUDED 1

#if defined(_MSC_VER)
// # include <Winsock.h>
# include <windows.h>
# include <tchar.h>
#else
# define TCHAR		char
# define _T(x)		x
# define _tprintf	printf
# define _tmain		main
#endif

#ifdef WIN32
	#define THREAD_ENTRYPOINT void
	#define ATHREAD unsigned long
	#define NULLTHREAD 0L
	#define THREAD_START(thread, entrypoint, parm) ((thread = _beginthread((entrypoint), 0, (void *)(parm))) != -1)
	#define THREAD_DETACH(thread)
	#define THREAD_EXIT(r) _endthread()
	#define SLEEP(nsec) Sleep(1000*(nsec))

	// #define INSTALL_AS_NT_SERVICE 1		//  !!! BUGGY DON'T SET !!!
#else
	#define THREAD_ENTRYPOINT void *
	#define ATHREAD pthread_t
	#define NULLTHREAD NULL
	#define THREAD_START(thread, entrypoint, parm) (pthread_create(&(thread), NULL, (entrypoint), (void *)(parm)) == 0)
	#define THREAD_DETACH(thread) pthread_detach(thread)
	#define THREAD_EXIT(r) pthread_exit(r)
	#define SLEEP(nsec)	{ struct timespec delay = { (nsec), 0 }; nanosleep(&delay, NULL); }
#endif


#endif

