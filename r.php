<?php

/** Merkur 5 initial demostration script inside the application directory
 * It containts minimalized presenter usage with some experimatal functionality,
 *  but all the tests are in test sub-folder 
 */ 

include_once 'lib/mlib.php';  /* core */
include_once 'lib/mbt.php';   /* bootstrap */

M5::set('header','Merkur 5');
M5::set('debug',true);
M5::set('routes',
  ['/'=>function(){htpr('Root');},
   '/hello'=>function(){htpr(bt_alert('Hello'));},
   '/hello/world'=>function(){htpr(bt_dialog('Route message','Hello World'));},
   '/doc/'=>function(){seznam_dok('test/doc');},
   '/doc/$file'=>function(){dokument(getpar('file'));},
   '/ahoj/svete'=>"dokument(15);",
   '/nazdar'=>'htpr(bt_dialog("Message","Hi"));'
  ]);

/* action before routing */
htpr(ta('h3','Sada nástrojů pro tvorbu webových aplikací'),
     'Stránka je vytvořena pomocí Merkur 5 - PHP frameworku pro tvorbu webových aplikací.',
     br(),
     'Samostatné "testovací" a demonstrační skripty (ne unit testy) jsou ve složce ',
     ahref(M5::get('path_relative').'/'.'test','testů').'.'.br(),
     'Soubory s dokumentací jsou ve virtuální složce ',
     ahref(M5::get('path_relative').'/'.'doc','dokumentací').'.'.br());

/* routing */     
if (($route=M5::getroute())!=''){
  //htpr(bt_alert('Path OK'));
  M5::set('header',M5::get('header').' - '.$route);
}else{
  htpr(bt_alert('Zadaná cesta nemá cíl ..','alert-warning'));
}

/* set all neseccary */
M5::skeleton(M5::get('path_relative').'/');
/* finish */
M5::done();

/*--------------------------------------------------------*/

/* functions */
function dokument($file){
  include_once "vendor/parsedown/Parsedown.php";
  htpr(tg('div','class="m-1 p-2 border bg-light"',
   Parsedown::instance()->text(file_get_contents('test/doc/'.$file))));
}

function seznam_dok($dir){
   $fls = scandir($dir, SCANDIR_SORT_ASCENDING);
   $lnk=[];
   for($i=0;$i<count($fls);$i++){
     if (preg_match('/^(.+)\.md$/',$fls[$i],$m)){
       array_push($lnk,ahref('doc/'.$fls[$i],$fls[$i]));
     }
   }
   for($s='',$i=0;$i<count($lnk);$i++) 
     $s.=ta('li',$lnk[$i]);
   $s=ta('ul',$s);
   htpr($s); 
}

function show_path(){
  htpr(ht_table('',['Parameter','Value'],
  [
   ['PHP version:',PHP_VERSION_ID],
   ['path current:',M5::get('path_current')],
   ['path relative:',M5::get('path_relative')],
   ['__DIR__ : ',__DIR__],
   ['REQUEST_METHOD',$_SERVER['REQUEST_METHOD']],
   ['REQUEST_URI',$_SERVER['REQUEST_URI']],
   ['SCRIPT_FILENAME',$_SERVER['SCRIPT_FILENAME']],
   ['REDIRECT_URL',$_SERVER['REDIRECT_URL']],
   ['QUERY_STRING',$_SERVER['QUERY_STRING']],

   
   ['SCRIPT_NAME',$_SERVER['SCRIPT_NAME']],
   ['DOCUMENT_ROOT',$_SERVER['DOCUMENT_ROOT']],

   ['PHP_SELF',$_SERVER['PHP_SELF']]
  ]));
}

?>