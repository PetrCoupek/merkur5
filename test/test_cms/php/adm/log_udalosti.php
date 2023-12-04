<?php
/**
 * aplikacni log udalosti
 * @author Petr Coupek
 * 7.10.2016 28.10.2022 04.12.2023
 */  

class aplikacni_log{

function __construct(){
}
  
function kostra($limit){
  $tabulka_log=App::$cms->table.'_LOG_TAB';

  htpr(tg('form','method="post"',
    gl(
     textfield('Filtr:','FILTR',20,50,getpar('FILTR')),nbsp(5),
     textfield('Limit:','limit',4,8,getpar('limit')?getpar('limit'):'500'),
     nbsp(5),
     submit('OK','Zobraz'),
     para('item',getpar('item')))),hr());

  if (preg_match('/^file=/',App::$dbconnect)){
     $db = new OpenDB_SQLite(App::$dbconnect);
  }elseif(preg_match('/^dsn=(.+);uid=(.+);pwd=(.+)$/i',App::$dbconnect)){
     $db = new OpenDB_Oracle(App::$dbconnect);
  }

  $omezeni=getpar('FILTR')?"where text like '%".getpar('FILTR')."%' ":'';

  if (preg_match('/^file=/',App::$dbconnect)){
    $prikaz="select datum as DATCAS, text from $tabulka_log ".$omezeni."order by datum desc";
  }else{
    $prikaz="select to_char(datum,'DD.MM.YYYY HH24:MI:SS') as datcas, text from $tabulka_log ".
      $omezeni."order by datum desc";    
  }    
  if (getpar('limit')){
    $limit=getpar('limit');
  }else{
    $limit=100;
  }
  
  $s='';
  foreach ($db->SqlFetchArray($prikaz,[],$limit) as $row)
    $s.=ta('tr',ta('td',ta('b',$row['DATCAS']).nbsp(2)).ta('td',$row['TEXT']));
  htpr(tg('table','',$s),hr());

}

}

$k=new aplikacni_log();
$k->kostra(500);

?>
