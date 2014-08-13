/* description: Parses Phraseanet search queries. */

/* lexical grammar */
%lex

/* lexical states */
%x literal

/* begin lexing */
%%

\s+                     /* skip whitespace */
"AND"                   return 'AND'
"and"                   return 'AND'
"et"                    return 'AND'
"OR"                    return 'OR'
"or"                    return 'OR'
"ou"                    return 'OR'
"IN"                    return 'IN'
"in"                    return 'IN'
"dans"                  return 'IN'
"("                     return '('
")"                     return ')'
'"'                     {
                            //js
                            this.begin('literal');
                            //php $this->begin('literal');
                        }
<literal>'"'            {
                            //js
                            this.popState();
                            //php $this->popState();
                        }
<literal>([^"])*   return 'LITERAL'
\w+                     return 'WORD'
<<EOF>>                 return 'EOF'

/lex


/* operator associations and precedence */

%left 'AND' 'OR'
%left 'IN'

%start query


%% /* language grammar */


query
    : expression EOF {
        //js
        console.log('[QUERY]', $$);
        return $$;
        //php return $$;
    }
    ;

expression
    : expression AND expression {
        //js
        $$ = '('+$1+' AND '+$3+')';
        console.log('[AND]', $$);
        //php $$ = sprintf('(%s AND %s)', $1->text, $3->text);
    }
    | expression OR expression {
        //js
        $$ = '('+$1+' OR '+$3+')';
        console.log('[OR]', $$);
        //php $$ = sprintf('(%s OR %s)', $1->text, $3->text);
    }
    | expression IN location {
        //js
        $$ = '('+$1+' IN '+$3+')';
        console.log('[IN]', $$);
        //php $$ = sprintf('(%s IN %s)', $1->text, $3->text);
    }
    | '(' expression ')' {
        //js
        $$ = $2;
        //php $$ = $2;
    }
    | text {
        //js
        $$ = '"'+$1+'"';
        console.log('[TEXT]', $$);
        //php $$ = sprintf('"%s"', $1->text);
    }
    ;

location
    : WORD
    ;

text
    : WORD
    | LITERAL
    ;


//option namespace:Alchemy\Phrasea\SearchEngine\Elastic
//option class:QueryParser
//option fileName:QueryParser.php
