# merkur5 ![M5](img/m5.png)

A set of PHP helpers and utilities to make the web database-driven applications easier.
Minimalistic and primitive.


Currently in progress ..


The design is a targeted to make simple server-side development with a minimum source code length and simple functionality .

The set constists of :
 
- the kernel library (static PHP class/module object called M5),
- database wrappers with unified behaviour, database connection objects with same methods for Oracle, SQLite, PostgreSQL and MySQL including inified parameter binding
- parts for visualization of the database entities 
- built-in content management system (**Cm::** module), minimal version can be stored in the SQLite database.
- included recommended vendor styles and front-end functionality (Bootstrap + JQuery included)
- included application template with **Cm::** module for starting make complex apps

## Concept and Ideas

- Root component is a **M5::** module. Most of its methods can be called as **global functions** . 
- The HTML/XML output is generated using function **ta()**, **tg()** and other global functions generating HTML/XML strings. 
 No HTML/XML tags in the code. All these functions are naturaly nested to generate output. The result is pushed into a
 buffer using **htpr()** function. The page template is separated from the code.
- Built-in set of frequently used SVG icons for tool buttons. 
- The input is processed via **getpar()** function with sanitization possibilities and optional defaults.
- More complex application are decomposed into a separate parts/componets using content management class **Cm::**, 
 which includes "user in group" rights management, application logging and standard left user-sensitive menu.
- The components have **route()** and/or **skeleton()** method. 
These methods are called to process the required application functionality 
- High portability: PHP versions from PHP 5.3 to PHP 7.4 are supported
- Simple debugging functionality with the **deb()** function, self-documentation 
functionality using the PHP reflexive class      

## Code example

### Object - oriented approach

```PHP

include_once '../lib/mlib.php'; 

class Hello extends M5{

 static function skeleton(){
   parent::skeleton('../');           /* implicit route */
   htpr(ta('h1','Minimal application'), 
        ta('p','That\'s it !'));      /* main action - print HTML page */
   htpr_all();                        /* buffer write */
 }

}

Hello::skeleton();  /* Do it */ ` 

``` 

### Sequencional approach with high effectivity of code

```PHP
include_once "../lib/mlib.php"; 
M5::skeleton('../'); 
htpr(ta('h1','Minimal application'),ta('p','That\'s it !')); 
htpr_all();                  
```
notice: these 4 lines of code generate a complex HTML/CSS styled page
  with one header and one paragraph of text based on default frontend template

### Primitive instant SQL workbech page with pragma functionality

```PHP
include_once "../lib/mlib.php"; /* nove aplikacni mikrojadro */
M5::skeleton('../');
htpr(tg('form','method="post" action="?" ',
     textarea('SQL command: [PHP ver.'.PHP_MAJOR_VERSION.']'.br(),
      'SQL',5,80,getpar('SQL')).
     submit('OK','OK')));
if ($sql=getpar('SQL')){
  $db=new OpenDB_MySQL("ser=server.somewhere;db=smallm;uid=smallm_cz;pwd=****");
  if (preg_match('/pragma\s+(.+)$/',$sql,$m)){
    htpr(ht_table('Pragma','',$db->Pragma($m[1])));
  }else{
    htpr(ht_table('Result','',$db->SqlFetchArray($sql)));
  }    
  $db->Close();
}  
htpr_all(); 
```
notice: the function **getpar()** is responsible for sanitization

### The database entity editor
```PHP
include_once '../lib/mlib.php';

class Table_editor extends M5{

 static function skeleton(){
   parent::skeleton('../');
   $t=new Edit_table('', 'file=../data/ep.sqlite,mode=1', 't1');
   $t->hledej_form=true;
   $t->route();
   htpr_all();
 }
 
} 

Table_editor::skeleton();
```  


