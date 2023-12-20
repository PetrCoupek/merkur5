<?php
/** Merkur 5 test index script
 * @author Petr Coupek
 */
//error_reporting(E_ALL);
session_start();


include_once '../lib/mlib.php';
include_once '../lib/mbt.php';
include_once "ini.php";


class MyTest extends M5{

 static function skeleton($path=''){
   parent::skeleton('../');           /* zajisti volani metody route, ../ je cesta k CSS */
   parent::set('header','Seznam testů k tomuto řešení');
   self::index();                      /* formular se tiskne vzdy */
   self::done();                        /* Zapis bufferu na standarni vystup */
 }
 
 static function index(){
   htpr('seznam');
   $fls = scandir('.', SCANDIR_SORT_ASCENDING);
   //htpr(ta('code',print_r($fls,true)));
   $lnk=[];
   for($i=0;$i<count($fls);$i++){
     if (preg_match('/^test(.+)\.php$/',$fls[$i],$m)){
       array_push($lnk,ahref($fls[$i],$fls[$i]));
     }
   }
   $s='';
   for($i=0;$i<count($lnk);$i++) $s.=ta('li',$lnk[$i]);
   $s=ta('ul',$s);
   htpr($s);     
 }
 
}

MyTest::skeleton(); /* volani skriptu */

?>