<?php
/** editace uzivatelu v cmsystemu
 * @author Petr Čoupek
 * 20.10.2022
 * 04.12.2023
 */  

/** trida zdedi listovani a vyhledavani zaznamu, 
   ale je doplnen editacni formular a ukladani zaznamu 
*/
define('EDIT_USER_ITEM',4); /* item in CMS with edit user form */

class Editace_skup extends EdiTab{
  
  var $pref;
  
function __construct($param,$db){
  parent::__construct($param,$db);
  $this->pref=App::$cms->table;
}

function detail_form_clenove($data=null,$context){
  $ciselnik=to_hash(
    "select ljmeno, prijmeni||' '||jmeno as jmp ".
    "from ".$this->pref."_uziv ".
    "order by prijmeni asc",
    $this->db);
  $ciselnik_bez=to_hash(
    "select ljmeno, prijmeni||' '||jmeno as jmp ".
    "from ".$this->pref."_uziv ".
    "where ljmeno not in (select uzivatel from ".$this->pref."_uskup where skupina='".$this->rowid."') ".
    "order by prijmeni asc, jmeno asc",
    $this->db);                   
  $a=$this->db->SqlFetchArray(
    "select skupina, uzivatel ".
    "from ".$this->pref."_uskup, ".$this->pref."_uziv ".
    "where skupina=:skupina and typ_vazby='U' ".
    "and ".$this->pref."_uskup.uzivatel=".$this->pref."_uziv.ljmeno ".
    "order by prijmeni asc, jmeno asc",
    [':skupina'=>$this->rowid]);
  //while ($this->db->FetchRow()){
  $cnt=[]; $del=4; 
  for($i=0;$i<count($a);$i++){
    array_push($cnt,[
      tg('form','method="post" style="display: inline;" action="?'.$context.'"',
       combo('','UZIVATEL',$ciselnik,$a[$i]['UZIVATEL']).nbsp($del).
       para('SKUPINA',$a[$i]['SKUPINA']).
       para('_o',getpar('_o')).para('_flt',getpar('_flt')).para('_ofs',getpar('_ofs')).   
       //submit('UPDuzivatele_','Uložit','btn btn-primary btn-sm').nbsp($del).
       submit('DELuzivatele_','Smazat','btn btn-secondary btn-sm m-1').nbsp($del)
       ).
       ahref('?_det=1&_o=&_flt=LJMENO~%3D~'.$a[$i]['UZIVATEL'].'&_ofs=1&item='.EDIT_USER_ITEM,
         'Editace uživatele','class="btn btn-secondary btn-sm m-1"')]);
  }
  /* prazdny radek nakonec*/ 
  array_push($cnt,[tg('form','method="post" style="display: inline;" action="?'.$context.'"',
    combo('','UZIVATEL',$ciselnik_bez).nbsp($del).
    para('SKUPINA',$this->rowid).
    para('_o',getpar('_o')).para('_flt',getpar('_flt')).para('_ofs',getpar('_ofs')).
    submit('INSuzivatele_','Přidat do skupiny','btn btn-primary btn-sm m-1'))]);
  return ta('fieldset',ta('legend','Členové skupiny').bt_container(['col-12'],$cnt));
}

function route($context){
  if (getpar('INSuzivatele_')){
    $this->vloz_uzivatele();
    $this->detail($context);
  }elseif (getpar('DELuzivatele_')){
    $this->smaz_uzivatele();
    $this->detail($context); 
  }else{
    parent::route($context);
  }
  if (getpar('_det')||getpar('INSuzivatele_')||getpar('DELuzivatele_')||getpar('UPDuzivatele_')){
    htpr(br(2),$this->detail_form_clenove(null,$context));
  }   
 }

function vloz_uzivatele(){
  $er=$this->db->Sql(
     "insert into ".$this->pref."_uskup (skupina,uzivatel,typ_vazby) ". 
     "values( :skupina, :uzivatel, 'U')",
     [':uzivatel'=>getpar('UZIVATEL'),
      ':skupina'=>getpar('SKUPINA')]);
  if (!$er){
    htpr(bt_alert('Záznam vložen'));
      //$this->detail($context);
  }else{
    htpr(bt_alert('Záznam nebyl uložen '.$this->db->Error,'alert-danger'));
  }
}

function smaz_uzivatele(){
  $er=$this->db->Sql("delete from ".$this->pref."_uskup where skupina=:skupina and uzivatel=:uzivatel ", 
                      [':uzivatel'=>getpar('UZIVATEL'),
                       ':skupina'=>getpar('SKUPINA')]);
  if (!$er){
    htpr(bt_alert('Záznam smazán'));
      //$this->detail($context);
  }else{
      htpr(bt_alert('Záznam nebyl smazán '.$this->db->Error,'alert-danger'));
  }
}

}

$db = new OpenDB_SQLite(App::$dbconnect);
$pref=App::$cms->table;

$tt= new Editace_skup(
  ['sprikaz'=>"select skupina, nazev  ".
              "from ".$pref."_skup order by skupina asc", 
   'cprikaz'=>'select count(*) as pocet from '.$pref.'_skup',
   'pragma'=>[['name'=> 'SKUPINA','comment' => 'ID skupiny','pk'=>1],
              ['name'=> 'NAZEV','comment' => 'Název skupiny'],
             ],
   'dprikaz'=>'select * from '.$pref.'_skup order by skupina asc',
   'uprikaz'=>["update ".$pref."_skup set nazev=:nazev, skupina=:SKUPINA ".
               "where skupina= '".getpar('skupina')."'",
               [
                ':SKUPINA'=>getpar('SKUPINA'), 
                ':nazev'=>getpar('NAZEV'),
                ]
              ],
   'iprikaz'=>["insert into ".$pref."_skup (skupina,nazev) ".
               "values (:skupina,:nazev) ",
               [':skupina'=>getpar('SKUPINA'),
                ':nazev'=>getpar('NAZEV')
                ]
              ],
    'rprikaz'=>["delete from ".$pref."_skup where skupina=:skupina",
              [':skupina'=>getpar('skupina')]
            ],
    'rowidcolumn'=>'SKUPINA'                           
   
  ],
  $db); 

$tt->route("&item=".getpar('item'));

$db->Close(); 
 

?>