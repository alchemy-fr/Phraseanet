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
"*"                     return '*'
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

%left 'WORD'
%left 'AND' 'OR'
%left 'IN'

%start query


%% /* language grammar */


query
    : expressions EOF {
        //js
        console.log('[QUERY]', $$);
        return $$;
        /*php
        return $$;
        */
    }
    ;

expressions
    : expression expressions {
        //js
        $$ = '('+$1+' DEF_OP '+$2+')';
        console.log('[DEF_OP]', $$);
        // $$ = sprintf('(%s DEF_OP %s)', $1->text, $2->text);
        /*php
        $$ = new AST\AndExpression($1->text, $2->text);
        */
    }
    | expression
    ;

expression
    : expression AND expression {
        //js
        $$ = '('+$1+' AND '+$3+')';
        console.log('[AND]', $$);
        /*php
        $$ = new AST\AndExpression($1->text, $3->text);
        */
    }
    | expression OR expression {
        //js
        $$ = '('+$1+' OR '+$3+')';
        console.log('[OR]', $$);
        /*php
        $$ = new AST\OrExpression($1->text, $3->text);
        */
    }
    | expression IN keyword {
        //js
        $$ = '('+$1+' IN '+$3+')';
        console.log('[IN]', $$);
        /*php
        $$ = new AST\InExpression($3->text, $1->text);
        */
    }
    | '(' expression ')' {
        //js
        $$ = $2;
        //php $$ = $2;
    }
    | prefix
    | text
    ;

keyword
    : WORD {
        //js
        $$ = '<'+$1+'>';
        console.log('[FIELD]', $$);
        //php $$ = new AST\KeywordNode($1->text);
    }
    ;

prefix
    : WORD '*' {
        //js
        $$ = $1+'*';
        console.log('[PREFIX]', $$);
        //php $$ = new AST\PrefixNode($1->text);
    }
    ;

text
    : WORD {
        //js
        $$ = '"'+$1+'"';
        console.log('[WORD]', $$);
        //php $$ = new AST\TextNode($1->text);
    }
    | LITERAL {
        //js
        $$ = '"'+$1+'"';
        console.log('[LITERAL]', $$);
        //php $$ = new AST\QuotedTextNode($1->text);
    }
    ;


//option namespace:Alchemy\Phrasea\SearchEngine\Elastic
//option class:QueryParser
//option use:Alchemy\Phrasea\SearchEngine\Elastic\AST as AST;
//option fileName:QueryParser.php
