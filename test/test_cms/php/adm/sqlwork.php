<?php

/** 
 * SQL Workbench pro administraci aplikace
 * refaktoring 04.12.2023
 */  
 
class SQL_work{
  var $connect,$db,$dbtype;

function __construct() {
  $this->connect=App::$dbconnect;
  if (preg_match('/^file=/',$this->connect)){
     $this->db = new OpenDB_SQLite($this->connect);
     $this->db_type='sqlite';
  }elseif(preg_match('/^dsn=(.+);uid=(.+);pwd=(.+)$/i',$this->connect)){
     $this->db = new OpenDB_Oracle($this->connect);
     $this->db_type='oracle';
  }
}

function __destruct(){
  $this->db->Close();
}

/** akce zobrazeni formulare */ 
function form(){
  htpr(ta('fieldset',ta('legend','SQL databáze '.$this->db_type). 
    tg('form','method="post"',gl(   
      textarea(http_lan_text('SQL command:','Příkaz SQL').br(),'COMM',3,105,
       getpar('COMM'),'style="width:100%"').br().  
      submit('B_SQL','SQL','btn btn-primary').
      nbsp(5).
      submit('B_CAT',http_lan_text('Catalog','Katalog'),'btn btn-primary').
      nbsp(5).
      textfield(http_lan_text('maxn ','maxn '),'LIM',3,5,getpar('LIM')).
      para('item',getpar('item'))))));
}

function katalog(){
  htpr(ht_table('',[],$this->db->Pragma('catalog'))); 
}
 
function konejsql($prikaz,$lim){
   $a=$this->db->SqlFetchArray($prikaz,[],$lim);   
   $kapsa=ht_table('',null,$a,'Žádná data.');
   if ($this->db->Error!='') htpr(bt_alert('Chyba :'.$this->db->Error,'alert-danger'));
   htpr(tg('div','style="background-color:white; position:relative; overflow:scroll; width: 90%; height:80%;"',
           tg('div','id="rpt"',$kapsa)),br());
}

function route(){
  $this->form();
  if (getpar('B_SQL')) $this->konejsql(getpar('COMM'),getpar('LIM')?getpar('LIM'):0);
  if (getpar('B_CAT')) $this->katalog();
}

}

$ta=new Sql_work();
$ta->route();


?>