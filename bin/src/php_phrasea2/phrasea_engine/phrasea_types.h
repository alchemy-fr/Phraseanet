#ifdef __ppc__	// os x
# define PH_INT32 int
# define PH_INT64 long long
# define PH_ATOI64(s)	atoll(s)
#else 
# ifdef WIN32	// windows
#  define PH_INT32 int
#  define PH_INT64 __int64
#  define PH_ATOI64(s)	_atoi64(s)
# else	// linux ?
#  define PH_INT32 int
#  define PH_INT64 __int64
#  define PH_ATOI64(s)	_atoi64(s)
# endif
#endif

// #define int PH_INT32



/* definition portable des structures systeme a denomination variable */
#ifndef PHRASEA_TIMEB
# ifdef noPHP_WIN32
#  define PHRASEA_TIMEB struct _timeb
#  define PHRASEA_FTIME _ftime
#  define PHRASEA_GET_MS millisec_diff
# else
#  define PHRASEA_TIMEB clock_t
#  define PHRASEA_FTIME phrasea_get_ticks
#  define PHRASEA_GET_MS phrasea_getclockinterval
// ******* tosee : ligne mise en remarque pour warning win32
# endif
#endif


#ifndef true
#define true TRUE
#endif

#ifndef false
#define false FALSE
#endif

#ifndef NONULLSTRING
#define NONULLSTRING(x) ((x)?(x):"")
#endif

#define PHRASEA_MYSQLENGINE 0
#define PHRASEA_PGSQLENGINE 1

#define PHRASEA_OP_NULL -1
#define PHRASEA_OP_OR 1
#define PHRASEA_OP_AND 2
#define PHRASEA_KW_ALL 3
#define PHRASEA_KW_LAST 4
#define PHRASEA_OP_EXCEPT 5
#define PHRASEA_OP_NEAR 6
#define PHRASEA_OP_BEFORE 7
#define PHRASEA_OP_AFTER 8
#define PHRASEA_OP_IN 9

#define PHRASEA_OP_EQUAL 10
#define PHRASEA_OP_NOTEQU 11
#define PHRASEA_OP_GT 12
#define PHRASEA_OP_LT 13
#define PHRASEA_OP_GEQT 14
#define PHRASEA_OP_LEQT 15
#define PHRASEA_OP_COLON 16

#define PHRASEA_KEYLIST 17

#define PHRASEA_KW_FIRST 18

#define MAXHITLENGTH 14

#define DEFAULTLAST 12

enum { PHRASEA_MULTIDOC_DOCONLY=0, PHRASEA_MULTIDOC_REGONLY };
enum { PHRASEA_ORDER_DESC=0, PHRASEA_ORDER_ASC=1, PHRASEA_ORDER_ASK=2 };

typedef struct	collid_pair
				{
					long local_base_id;	// id de base locale (champ 'base_id' dans la table 'bas' de la base 'phrasea' locale)
					long distant_coll_id;	// id de collection distante (champ 'coll_id' dans la table 'coll' de la base distante)
				}
				COLLID_PAIR;

typedef struct  spot
				{
					int start;
					int len;
					struct spot *nextspot;
				}
				SPOT;

typedef struct	hit
				{
					int iws;
					int iwe;
					// char hit[MAXHITLENGTH+1];
					struct hit *nexthit;
				}
				HIT;

class CSHA256
{
	private:
		unsigned char _v[65];
	public:
		CSHA256()
		{
			memset(this->_v, 0, sizeof(this->_v));
		}
		~CSHA256()
		{
		}
		CSHA256 &operator=(const unsigned char *v)
		{
			memset(this->_v, 0, sizeof(this->_v));
			if(v)
			{
				unsigned char *p=this->_v;
				for(register int i=0 ; i<64 && *v; i++)
					*p++ = *v++;
			}
			return *this;
		}
		CSHA256 &operator=(const CSHA256 &rhs)
		{
			memcpy(this->_v, rhs._v, sizeof(this->_v));
			return *this;
		}
		bool operator==(const CSHA256 &rhs)
		{
			return(strcmp((const char *)(this->_v), (const char *)(rhs._v))==0);
		}
		bool operator!=(const CSHA256 &rhs)
		{
			return(!(*this==rhs));
		}
		operator const char *()
		{
			return((const char *)(this->_v));
		}
};

typedef struct	answer
				{
					int rid;
					int prid;
					int cid;
					unsigned long long llstatus;	// 64 bits
					CSHA256 osha256;
					HIT *firsthit, *lasthit;
					SPOT *firstspot, *lastspot;
					int nspots;
					// SPOT *spots;
					// int *sqloffsets;
					struct answer *nextanswer;
				}
				ANSWER;


typedef struct	keyword
				{
					char *kword;
					struct keyword *nextkeyword;
				}
				KEYWORD;

typedef struct	s_node
				{
					int type;
					bool isempty;
					ANSWER *firstanswer, *lastanswer;
					int nbranswers;
					int nleaf;
					double time_C;
					double time_sqlQuery, time_sqlStore, time_sqlFetch;
					union
					{
						struct
						{
							int v;
						} numparm;
						struct
						{
							char *kword;
						} leaf;
						struct
						{
							KEYWORD *firstkeyword, *lastkeyword;
						} multileaf;
						struct
						{
							struct s_node *l;
							struct s_node *r;
							int numparm;
						} boperator;
						struct
						{
							char *fieldname;
							char *strvalue;
							double dblvalue;
						} voperator;
					}
					content;
				}
				NODE;



// les structures 'calqu?es' des donn?es en cache
typedef struct  cache_spot
				{
					unsigned int start;
					unsigned int len;
				}
				CACHE_SPOT;
typedef struct	cache_answer
				{
					int rid;
					int prid;
					int bid;
					unsigned long long llstatus;		// 64 bits
					unsigned int spots_index;
					unsigned int nspots;
				}
				CACHE_ANSWER;

// impl?mentation d'une resource "phrasea_connection" : en fait une connexion ? pg ou mysql
// http://groups.google.fr/groups?q=zend_register_list_destructors_ex+persistent&hl=fr&lr=&ie=UTF-8&selm=cvshelly1039456453%40cvsserver&rnum=8

typedef struct	_php_phrasea_conn
				{
					int sqlengine;
					union
					{
#ifdef PGSUPPORT
						PGconn *pgsql_conn;
#endif
						MYSQL mysql_conn;
					}
					sqlconn;
					int mysql_active_result_id;
				}
				PHP_PHRASEA_CONN;
