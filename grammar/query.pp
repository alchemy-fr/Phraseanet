%token  space           \s+

// Symbols
%token  parenthese_     \(
%token _parenthese      \)
%token  bracket_        \[
%token _bracket         \]
%token  colon           :
%token  lte             <=|≤
%token  gte             >=|≥
%token  lt              <
%token  gt              >
%token  equal           =

// Strings
%token  quote_          "        -> string
%token  string:quoted   [^"]+
%token  string:_quote   "        -> default
%token  raw_quote_      r"       -> raw
%token  raw:raw_quoted  (?:(?>[^"\\]+)|\\.)+
%token  raw:_raw_quote  "        -> default

// Operators (too bad we can't use preg "i" flag)
%token  and             [Aa][Nn][Dd]|[Ee][Tt]
%token  or              [Oo][Rr]|[Oo][Uu]
%token  except          [Ee][Xx][Cc][Ee][Pp][Tt]|[Ss][Aa][Uu][Ff]

// Rest
%token  database        database
%token  collection      collection
%token  type            type
%token  id              id|recordid
%token  created_on      created_(on|at)
%token  updated_on      updated_(on|at)
%token  field_prefix    field\.
%token  flag_prefix     flag\.
%token  meta_prefix     (?:meta|exif)\.
%token  true            true|1
%token  false           false|0
%token  word            [^\s\(\)\[\]:<>≤≥=]+

// relative order of precedence is NOT > XOR > AND > OR

#query:
    ::space::? primary()? ::space::?
  | catch_all()

catch_all:
  ( <space>
  | <word>
  | keyword()
  | symbol() #text )*

// Boolean operators

primary:
    secondary() ( ::space:: ::except:: ::space:: primary() #except )?

secondary:
    ternary() ( ::space:: ::or:: ::space:: primary() #or )?

ternary:
    quaternary() ( ::space:: ::and:: ::space:: primary() #and )?

quaternary:
    group() #group
  | key_value_pair() ( ::space:: primary() #and )?
  | term() ( ::space:: key_value_pair() #and )?

// Key value pairs & field level matchers (restricted to a single field)

key_value_pair:
    match_key()              ::colon:: ::space::? value()   #match_expression
  | ::flag_prefix::  flag()  ::colon:: ::space::? boolean() #flag_statement
  | ::field_prefix:: field() ::colon:: ::space::? term()    #field_statement
  |                  field() ::colon:: ::space::? term()    #field_statement
  | key() ::space::?         ::lt::    ::space::? value()   #less_than
  | key() ::space::?         ::gt::    ::space::? value()   #greater_than
  | key() ::space::?         ::lte::   ::space::? value()   #less_than_or_equal_to
  | key() ::space::?         ::gte::   ::space::? value()   #greater_than_or_equal_to
  | key() ::space::?         ::equal:: ::space::? value()   #equal_to

match_key:
    native_key()
  | ::meta_prefix:: meta_key()

#native_key:
    <database>
  | <collection>
  | <type>
  | <id>

key:
    timestamp_key()
  | ::meta_prefix::  meta_key()
  | ::field_prefix:: field_key()
  |                  field_key()

#timestamp_key:
    <created_on>
  | <updated_on>

#meta_key:
  word_or_keyword()+

#field_key:
    word_or_keyword()+
  | quoted_string()

#flag:
  word_or_keyword()+

#field:
    word_or_keyword()+
  | quoted_string()

#value:
    word_or_keyword()+
  | quoted_string()

group:
    ::space::? ::parenthese_:: ::space::? primary() ::space::? ::_parenthese:: ::space::?

// Thesaurus terms

term:
    ::bracket_:: text() ::_bracket:: #thesaurus_term
  | text() #text


// Free text handling

text:
    string()
  ( <space>? string() )*
  ( ::space::? context_block() )?

context_block:
    ::parenthese_:: ::space::? context() ::space::? ::_parenthese:: #context

context:
   word_or_keyword() ( <space>? word_or_keyword() )*

// Generic helpers

boolean:
    <true>
  | <false>

string:
    word_or_keyword()+
  | quoted_string()
  | raw_quoted_string()

word_or_keyword:
    <word> | keyword()

quoted_string:
    ::quote_:: <quoted> ::_quote::

raw_quoted_string:
    ::raw_quote_:: <raw_quoted> ::_raw_quote::

keyword:
    <except>
  | <and>
  | <or>
  | <database>
  | <collection>
  | <type>
  | <id>
  | <created_on>
  | <updated_on>
  | <field_prefix>
  | <flag_prefix>
  | <meta_prefix>
  | <true>
  | <false>

symbol:
    <parenthese_>
  | <_parenthese>
  | <bracket_>
  | <_bracket>
  | <colon>
