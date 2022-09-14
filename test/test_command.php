<?php

/**
  Testovaci klient pro overeni funkcnosti SOAP volani
  CRZP / AIS
  test : command line Merkur 5 - spusteni i z prikazoveho radku/ nebo webu
  
*/

include_once "../lib/mlib.php"; /* Merkur5 helper */
include_once "../lib/mbt.php";
M5::skeleton('../');           /* Merkur5 - > route -> getparm -> nastaveni sapi_name */
M5::set('debug',true);       /* nastaveni debug */ 
M5::set('header','Test - command ');

M5::set('immediate',true);

deb(getpar('info')); /* test predaneho parametru, pokud je vykonan M5::skeleton() */

for($i=0;$i<10;$i++) htpr('Ahoj.'.$i,br());

deb($_GET);deb($_POST);
deb(M5_core::get('sapi_name'));
deb($DATA);


htpr_all();

?>