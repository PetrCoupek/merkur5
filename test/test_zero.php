<?php
/** Merkur 5 zero application
 * @author Petr Coupek
 * @date 07.02.2022
 */

include_once '../lib/mlib.php';
M5::set('header','Zero test');
M5::skeleton('../');
htpr(ta('h1','Hello'),
     'This is the zero functionality test ', br(),
     ht_table('',['Parameter','Value'],
      [['PHP version:',PHP_VERSION_ID],
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
       ['PHP_SELF',$_SERVER['PHP_SELF']],
       ['M5::$ent',ta('code',str_replace("\n",'<br>',htmlspecialchars(print_r(M5::$ent,true))))]
      ])
);
M5::done();

?>