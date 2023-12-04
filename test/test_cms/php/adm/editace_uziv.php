<?php
/** editace uzivatelu v cmsystemu
 * @author Petr Čoupek
 * 20.10.2022
 * 04.12.2023
 */  

/** trida zdedi listovani a vyhledavani zaznamu, 
   ale je doplnen editacni formular a ukladani zaznamu 
*/

class Editace_uziv extends EdiTab{

 /** reseni extra funkcionality nad ramec ukladani do jedne entity */
 function route_skupina_uziv(){
  if (getpar('_det')){
    /* zobraz, ve kterych skupinach je uzivatel  */
    htpr(
     ht_table('Členství ve skupinách',
      ['NAZEV'=>'Název skupiny'],
      $this->db->SqlFetchArray(
       "select nazev ".
       "from dkb_uskup,dkb_skup ".
       "where dkb_uskup.skupina=dkb_skup.skupina ".
       "and typ_vazby='U' ".
       "and dkb_uskup.uzivatel=:u",
       [':u'=>$this->rowid]),
      'Uživatel není ve skupinách',
      'class="table"'));
  }    
 }

}

$db = new OpenDB_SQLite(App::$dbconnect);
$pref=App::$cms->table;
$tt= new Editace_uziv(
  ['sprikaz'=>
     "select ljmeno, uziv_id, prijmeni, jmeno,  ".
     "odbor, oddeleni, kemail ".
     "from ".$pref."_uziv order by prijmeni asc", 
   'cprikaz'=>'select count(*) as pocet from '.$pref.'_uziv',
   'pragma'=>[
     ['name'=> 'LJMENO','comment' => 'Uživatelské jméno'],
     ['name'=> 'UZIV_ID','comment' => 'ID'],
     ['name'=> 'JMENO','comment' => 'Jméno'],
     ['name'=> 'PRIJMENI','comment' => 'Příjmení'],  
     ['name'=> 'OS_CISLO','comment' => 'Os. číslo'],
     ['name'=> 'TITUL_PRED','comment' => 'Titul před','nolist'=>true],
     ['name'=> 'TITUL_ZA','comment' => 'Titul za'],
     ['name'=> 'ODBOR','comment' => 'Odbor'],
     ['name'=> 'ODDELENI','comment' => 'Oddělení'],
     ['name'=> 'CISLO_KANC','comment' => 'Číslo kanceláře','nolist'=>true],
     ['name'=> 'SPECIALIZACE','comment' => 'Specializace','nolist'=>true],
     ['name'=> 'ULICE','comment' => 'Ulice','nolist'=>true],
     ['name'=> 'MESTO','comment' => 'Město','nolist'=>true],
     ['name'=> 'PSC','comment' => 'PSČ','nolist'=>true],
     ['name'=> 'KEMAIL','comment' => 'e-mail','nolist'=>true],
     ['name'=> 'KTELEFON','comment' => 'Telefon','nolist'=>true],
     ['name'=> 'KMOBIL','comment' => 'Mobil','nolist'=>true]],
   'dprikaz'=>'select * from '.$pref.'_uziv order by prijmeni asc',
   'uprikaz'=>[
     'update '.$pref.'_uziv set jmeno=:jmeno,'.
     'prijmeni=:prijmeni, os_cislo=:os_cislo, titul_pred=:titul_pred,'.
     'titul_za=:titul_za,odbor=:odbor,oddeleni=:oddeleni,cislo_kanc=:cislo_kanc,'.
     'specializace=:specializace,ulice=:ulice,mesto=:mesto,psc=:psc,kemail=:kemail,'.
     'ktelefon=:ktelefon,kmobil=:kmobil '.
     'where uziv_id=:uziv_id',
     [':ljmeno'=>getpar('LJMENO'),
      ':uziv_id'=>getpar('UZIV_ID'),
      ':jmeno'=>getpar('JMENO'),
      ':prijmeni'=>getpar('PRIJMENI'),
      ':os_cislo'=>getpar('OS_CISLO'),
      ':titul_pred'=>getpar('TITUL_PRED'),
      ':titul_za'=>getpar('TITUL_ZA'),
      ':odbor'=>getpar('ODBOR'),
      ':oddeleni'=>getpar('ODDELENI'),
      ':cislo_kanc'=>getpar('CISLO_KANC'),
      ':specializace'=>getpar('SPECIALIZACE'),
      ':ulice'=>getpar('ULICE'),
      ':mesto'=>getpar('MESTO'),
      ':psc'=>getpar('PSC'),
      ':kemail'=>getpar('KEMAIL'),
      ':ktelefon'=>getpar('KTELEFON'),
      ':kmobil'=>getpar('KMOBIL')
     ]],
   'iprikaz'=>[
     "insert into '.$pref.'_uziv (ljmeno,lheslo,uziv_id,jmeno,prijmeni,os_cislo,titul_pred,".
     "titul_za,odbor,oddeleni,cislo_kanc,specializace,ulice,mesto,psc,kemail,".
     "ktelefon,kmobil) ".
     "values (:ljmeno,'*',:uziv_id,:jmeno,:prijmeni,:os_cislo,:titul_pred,".
     ":titul_za,:odbor,:oddeleni,:cislo_kanc,:specializace,:ulice,:mesto,:psc,:kemail,".
     ":ktelefon,:kmobil) ",
     [':ljmeno'=>getpar('LJMENO'),
      ':uziv_id'=>getpar('UZIV_ID'),
      ':jmeno'=>getpar('JMENO'),
      ':prijmeni'=>getpar('PRIJMENI'),
      ':os_cislo'=>getpar('OS_CISLO'),
      ':titul_pred'=>getpar('TITUL_PRED'),
      ':titul_za'=>getpar('TITUL_ZA'),
      ':odbor'=>getpar('ODBOR'),
      ':oddeleni'=>getpar('ODDELENI'),
      ':cislo_kanc'=>getpar('CISLO_KANC'),
      ':specializace'=>getpar('SPECIALIZACE'),
      ':ulice'=>getpar('ULICE'),
      ':mesto'=>getpar('MESTO'),
      ':psc'=>getpar('PSC'),
      ':kemail'=>getpar('KEMAIL'),
      ':ktelefon'=>getpar('KTELEFON'),
      ':kmobil'=>getpar('KMOBIL')
     ]],
    'rprikaz'=>[
      "delete from '.$pref.'_uziv where uziv_id=:uziv_id",
      [':uziv_id'=>getpar('UZIV_ID')]],
    'rowidcolumn'=>'LJMENO'                           
   
  ],
  $db); 

$tt->route("&item=".getpar('item'));

/* extra funkcionalita */
$tt->route_skupina_uziv();
//deb($tt->rowid);  

$db->Close(); 
 

?>