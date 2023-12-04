<?php
/**
 * pomucka pro zobrazeni uzivatelu ve skupinach v konkretni aplikaci
 * @author Petr Coupek
 * @date 23.06.2020 17.09.2020 
 * 04.12.2023
 */  

class prehled_pristup{

/** konstruktor tabulky
 * @param $tab - prefix tabulek s informacemi CMS systému
 * @param $connect - informace pro připojení k databázi s CMS systémem
 */ 
function __construct($tab,$connect){
  $this->connect=$connect;
  $this->tab=$tab;
  if (preg_match('/^file=/',$this->connect)){
     $this->db = new OpenDB_SQLite($this->connect);
     $this->db_type='sqlite';
  }elseif(preg_match('/^dsn=(.+);uid=(.+);pwd=(.+)$/i',$this->connect)){
     $this->db = new OpenDB_Oracle($this->connect);
     $this->db_type='oracle';
  }
}

/** metoda generujici krizovou tabulku pristupu uzivatel/skupina
 */ 
function tabulka(){
  $tab=$this->tab;
  $tabulka_skupiny=$tab.'_USKUP';
  $tabulka_sez_skupin=$tab.'_SKUP';
  $tabulka_uzivatele=$tab.'_UZIV';
  $S=array();
  $T=array();
  $U=array();
  $db=$this->db;
  $db->Sql("select skupina, nazev from $tabulka_sez_skupin order by nazev");
  while ($db->FetchRow()){
    $S[$db->Data('SKUPINA')]=$db->Data('NAZEV');
    $T[$db->Data('SKUPINA')]=array();
  }
  $db->Sql("select jmeno||' '||prijmeni as jm, ljmeno from $tabulka_uzivatele order by prijmeni");
  while ($db->FetchRow()){
    $U[$db->Data('LJMENO')]=$db->Data('JM');
  }  
  $db->Sql("select skupina, uzivatel from $tabulka_skupiny where typ_vazby='U'");  
  while ($db->FetchRow()){
    $T[$db->Data('SKUPINA')][$db->Data('UZIVATEL')]='X';
  }
  $r=ta('th','Uživatel/Skupina');
  foreach ($S as $kk=>$vv){
    $r.=ta('th',$vv);
  }
  $r=ta('tr',$r);
  foreach ($U as $k=>$v){
    $rr=tg('td','class="sede"',$v);
    foreach ($S as $kk=>$vv){
      $rr.=ta('td',isset($T[$kk][$k])?'X':'-');
    }
    $r.=ta('tr',$rr);  
  }
  htpr(tg('table','class="table table-bordered table-hover table-sm"',
    ta('caption','Tabulkový přehled přístupů').
    $r));
}
  
function kostra(){
   
  $this->tabulka();  
}

}

//deb(App::$cms->table);
//deb(App::$napojeni);
$k=new prehled_pristup(App::$cms->table,App::$dbconnect);
$k->kostra();

?>