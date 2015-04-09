%token  space           \s+

// Symbols
%token  parenthese_     \(
%token _parenthese      \)
%token  bracket_        \[
%token _bracket         \]
%token  colon           :

// Strings
%token  quote_          "        -> string
%token  string:quoted   [^"]+
%token  string:_quote   "        -> default

// Operators (too bad we can't use preg "i" flag)
%token  in              [Ii][Nn]
%token  and             [Aa][Nn][Dd]
%token  or              [Oo][Rr]
%token  except          [Ee][Xx][Cc][Ee][Pp][Tt]

// Rest
%token  collection      collection
%token  word            [^\s\(\)\[\]]+

// relative order of precedence is NOT > XOR > AND > OR

#query:
    ::space::? primary()? ::space::?

primary:
    secondary() ( ::space:: ::except:: ::space:: primary() #except )?

secondary:
    ternary() ( ::space:: ::or:: ::space:: primary() #or )?

ternary:
    quaternary() ( ::space:: ::and:: ::space:: primary() #and )?

quaternary:
    collection_filter() #collection | quinary()

collection_filter:
    ::collection:: ::colon:: string()

quinary:
    senary() ( ::space:: ::in:: ::space:: string() #in )?

senary:
    group() #group
  | term()

group:
    ::space::? ::parenthese_:: primary() ::_parenthese:: ::space::?

term:
    ( bracketed_text() #thesaurus_term )
  | ( text() #text )

bracketed_text:
    ::bracket_:: text() ::_bracket::

text:
    string_keyword_symbol()
  ( <space>? string_keyword_symbol() )*
  ( ::space::? context() )?

string_keyword_symbol:
    string()
  | symbol()

#context:
    ::parenthese_:: ::space::? string() ::space::? ::_parenthese::

string:
    word_or_keyword() ( <space>? word_or_keyword() )*
  | quoted_string()

word_or_keyword:
    <word> | keyword()

quoted_string:
    ::quote_:: <quoted> ::_quote::

keyword:
    <in>
  | <except>
  | <and>
  | <or>
  | <collection>

symbol:
    <parenthese_>
  | <_parenthese>
  | <bracket_>
  | <_bracket>
  | <colon>
