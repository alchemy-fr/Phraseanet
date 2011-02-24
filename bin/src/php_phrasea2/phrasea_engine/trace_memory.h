// -------------------------------------------------------------------------------------------------
// ------------------------------------------------ allocation m�moire trac�e ----------------------
#if 1
	#define EMALLOC(s) emalloc(s)
	#define EFREE(p) efree(p)
	#define ESTRDUP(p) estrdup(p)
	#define TRACELOG(s) void()
#else
	void *my_emalloc(int s, const char *file, int line);
	void my_efree(void *p2, const char *file, int line);
	#define EMALLOC(s) my_emalloc(s, __FILE__, __LINE__)
	#define EFREE(p) my_efree(p, __FILE__, __LINE__)

	char *my_estrdup(char *s);
	#define ESTRDUP(p) my_estrdup(p)

	void tracelog(const char *s, const char *file, int line);
	#define TRACELOG(s) tracelog((s), __FILE__, __LINE__)
#endif
// -------------------------------------------------------------------------------------------------


