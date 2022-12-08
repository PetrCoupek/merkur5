<?php

/** Merkur 5 Initial script inside the application directory
 */ 

include_once 'lib/mlib.php';
include_once 'lib/mbt.php';

M5::set('header','Merkur 5');

M5::set('debug',true);
M5::set('routes',
  ['/'=>function(){htpr('Root');},
   '/hello'=>function(){htpr(bt_alert('Hello'));},
   '/hello/world'=>function(){htpr(bt_dialog('Route message','Hello World'));},
   '/doc/$id'=>function(){dokument(getpar('id'));},
   '/ahoj/svete'=>"dokument(15);",
   '/nazdar'=>'htpr(bt_dialog("Message","Hi"));'
  ]);

/*  action before routing */
htpr(ta('h1','Micro framework'),
     'This is the initial functionality. ',
     'All standalone tests are in '.
     ahref(M5::get('path_relative').'/'.'test','test folder').br().
     ' The test folder has an exception - see mod_rewrite settings '.
     ' and the PHP code functionality. ', br());

/* routing */     
if (($route=M5::getroute())!=''){
  htpr(bt_alert('Path OK'));
  M5::set('header',M5::get('header').' - '.$route);
}else{
  htpr(bt_alert('No path there ..','alert-warning'));
}
M5::skeleton(M5::get('path_relative').'/');
M5::done();

/*--------------------------------------------------------*/

function dokument($id){
  htpr('Dokument '.$id);
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

//  if( $_SERVER['REQUEST_METHOD'] == 'DELETE' ){ route($route, $path_to_include); }    }


?>