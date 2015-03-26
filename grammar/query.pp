%token  space           \s+

// Symbols
%token  parenthese_     \(
%token _parenthese      \)
%token  bracket_        \[
%token _bracket         \]

// Strings
%token  quote_          "        -> string
%token  string:quoted   [^"]+
%token  string:_quote   "        -> default
%token  colon           :

// Operators
%token  in              IN
%token  and             AND
%token  or              OR
%token  except          EXCEPT

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
  | keyword()
  | symbol()

#context:
    ::parenthese_:: string()+ ::_parenthese::

string:
    <word>
  | quoted_string()

quoted_string:
    ::quote_:: <quoted> ::_quote::

keyword:
    <in>
  | <except>
  | <and>
  | <or>
  | <collection>

symbol:
    ::parenthese_::
  | ::_parenthese::
  | ::bracket_::
  | ::_bracket::
  | ::colon::
