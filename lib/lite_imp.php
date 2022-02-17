<?php
/** SQLite import - funkcionalita importu databazovych tabulek - protikus oraexp
 *  @author Petr Coupek
 *  @date 02.07.2019
 *  je cten csv a inf soubor a po prevodu do UTF8 je to vlozeno do SQLITE databaze
 *  16.07.2019 - upravy
 *  24.07.2019 - upravy 
 *  15.01.2020 - upravy
 *  22.01.2020 - import csv
 */

include "lib/libdbSQLite.php"; 

class lite_imp {

function __construct($napojeni){
  $this->napojeni=$napojeni;  /* prazdny retezec vyvola jen vypis prikazu */
  $this->odkud='export/';
  $this->oddelovac=';';
  $this->verbose=true;  /* vydava echo pri importu */
  $this->textOnly=($napojeni=='');  /* true zpusobi jen tisk prikazu, nejsou vykonany */
  if (!$this->textOnly){
    $a=$this->db = new OpenDB_SQLite($napojeni);
  }                                                    
  $this->conv=false;  /* zda provadet konverzi do UTF-8 Pokud FALSE, jiz je zdroj v UTF-8*/  
}

function __destruct(){
  if (!$this->textOnly){
    $this->db->Close();
  }  
}

function importuj($soubor,$tabulka,$odkud=''){
  /* provede ímport tabulky do schematu */
  if ($odkud!='') $this->odkud=$odkud;
  /* definicni soubor */
  $info=$this->odkud.$soubor.'.inf';
  $data=$this->odkud.$soubor.'.csv';
  if (!file_exists($info)){
    $this->hlaseni("Soubor $info s definici struktury tabulky nebyl nalezen.");
    return 0;
  }
  if (!file_exists($data)){
    $this->hlaseni("Soubor $data s daty tabulky nebyl nalezen.");
    return 0;
  }
  if ($tabulka==''){
    /* prazdna tabulka znaci, ze se vyuzije jmeno souboru, ale odstrani se z nej schema */
    //$tabulka=strtolower(str_replace('GF_KOD.','',$soubor));
    if (strpos($soubor,'.')===false){
      $this->hlaseni('Pokud neni uvedeno jmeno tabulky, musi byt prvni soubor vcetne schematu.'); return 0;
    }
    $tabulka=substr($soubor,strpos($soubor,'.')+1);
  }
  
  $f=fopen($info,"r");
  $prikaz='';
  while(true){
    $r=fgets($f);
    if ($r===false) break;
    if ($this->conv) $r=iconv("windows-1250","utf-8//TRANSLIT",$r);
    $r=str_replace(array("\r", "\n"), '', $r);
    if (preg_match("/^table\s+(.*)\($/",$r,$m)){
      $r='create table '.$tabulka.'(';
    }elseif($r=='primary key ()'){
      $r=''; /* puvodni tabulka byla bez primarniho klice - ignoruj radek 
         a odstran carku na predchozim radku*/
      $prikaz=substr($prikaz, 0, strrpos($prikaz,',  --')). substr($prikaz,strrpos($prikaz,',  --')+1 );
    }else{
      /* zde jsou dulezite pocatecni mezery - v komentari se pak uchova puvodni datovy typ */
      $r=preg_replace("/ VARCHAR2\(\d+\)/",' text',$r);
      $r=preg_replace("/ VARCHAR\(\d+\)/",' text',$r);
      $r=preg_replace("/ NVARCHAR2\(\d+\)/",' text',$r);
      $r=preg_replace("/ NVARCHAR\(\d+\)/",' text',$r);
      $r=preg_replace("/ CHAR\(\d+\)/",' text',$r);
      $r=preg_replace("/ NUMBER\(\d+,\d+\)/",' real',$r);
      $r=preg_replace("/ NUMBER\(\d+\)/",' integer',$r);
      $r=preg_replace("/ NUMBER/",' integer',$r);
      $r=preg_replace("/ CLOB\(\d+\)/",' text',$r);
      $r=preg_replace("/ NCLOB\(\d+\)/",' text',$r);
      $r=preg_replace("/ CLOB/",' text',$r);
      $r=preg_replace("/ NCLOB/",' text',$r);
      $r=preg_replace("/ DATETIME\(\d+\)/",' text',$r); /* zpetna komp. - nejprve odstran slozitejsi */
      $r=preg_replace("/ DATE\(\d+\)/",' text',$r);     /* zpetna komp. - nejprve odstran slozitejsi */
      $r=preg_replace("/ DATETIME/",' text',$r);
      $r=preg_replace("/ DATE/",' text',$r);
      $r=preg_replace("/ LONG(\d+)/",' text',$r);
      $r=preg_replace("/ LONG/",' text',$r);
    }    
    $prikaz.=$r."\n";
  }
  //echo "$prikaz ;\n";
  //$this->db->conn->exec('BEGIN');
  $this->konej('BEGIN');
  if (!$this->konej($prikaz)){  
    $this->hlaseni("Chyba SQL: / ".$prikaz);
    return 0;
  }
  //$this->db->conn->exec('COMMIT');
  $this->konej('COMMIT');
  fclose($f);
  $f=fopen($data,"r");
  $k=0; 
  $n=0;
  $this->konej('BEGIN');
  while(true){
    $r=fgets($f);
    if ($r===false) break;
    //$r=iconv("windows-1250","utf-8//TRANSLIT",$r);    
    if ($k==0){
      /* prvni radek obsahuje hlavicky */
      $pole=explode($this->oddelovac,$r);
      /* pokud je hlavicka ve tvaru prevedene datumove polozky, pak se bere to, co je za 'as'*/
      for($i=0,$nn=count($pole);$i<$nn;$i++){
        if (preg_match("/^(.+)\s+as\s+(\w+)$/",$pole[$i],$m)){
          $pole[$i]=$m[2];
        }
      }
    }else{
      $hodnoty=explode($this->oddelovac,$r);
      $nenul=true;
      for($i=0;$i<count($hodnoty);$i++){
        if ($hodnoty[$i]=='') {
          $hodnoty[$i]='null'; $nenul=false;
        }else{
          $nenul=true;
        }  
         /* pokud je posledni hodnota na radku numericka a neni uvedena, do promenne hodnoty[i] se dostane x0a */
        if ($i==count($hodnoty)-1 && $hodnoty[$i]==chr(10)){
           $hodnoty[$i]='null';
        } 
        if ($this->conv) $hodnoty[$i]=iconv("windows-1250","utf-8//TRANSLIT",$hodnoty[$i]);
        if ($hodnoty[$i]=='' && $nenul){
           $hodnoty[$i]='"-"'; /*tj. konverze se nepovedla */
           $this->hlaseni('radek '.$k.' hodnotu nelze prevest do UTF-8');
        }   
        if (strstr($hodnoty[$i],'\"')){
          $hodnoty[$i]=str_replace('\"','"||char(34)||"',$hodnoty[$i]);
        }
      }
     
      $prikaz="insert into $tabulka (".implode($pole,', ').') values ('.implode($hodnoty,', ').')';
      /*if ($k==9621) {
        echo $prikaz; 
        print_r($hodnoty);
        for ($i=0;$i<$nn;$i++) echo $i,' ',strlen($hodnoty[$i]),' ',implode(unpack("H*", $hodnoty[$i])),"\n";
        print_r($pole);
        break;
      } */
      if ($this->konej($prikaz)){
         $n++;
      }else{
        $this->hlaseni('radek '.$k.' '.$prikaz);
      }
      if ($this->verbose && !($n%100) ) echo "$n\r";       
    }
    $k++;
  }
  $this->konej('COMMIT');
  if (!$this->textOnly){
    $this->hlaseni("Importovano $n zaznamu do $tabulka.");
  }
  return 1;  
}

function importuj_schema($odkud){
  /* seznam souboru ve slozce odkud */
  $sou=scandir($odkud);
  for($i=0;$i<count($sou);$i++){
    if (preg_match("/^(\w+)\.(\w+)\.inf$/",$sou[$i],$m)){
      $schema=$m[1]; $tab=$m[2];
      $this->hlaseni("Soubor ".$sou[$i]." :");
      $vysledek=$this->importuj($m[1].'.'.$m[2],$tab,$odkud);
      if (!$vysledek){
        $this->hlaseni("$tab - nenahrano");
        break;
      }  
    }  
  }
}

function pripoj_csv($soubor,$tabulka,$odkud=''){
  /* jen provede ímport dat ze souboru csv do existujici tabulky ve schematu */
  if ($odkud!='') $this->odkud=$odkud;
  $data=$this->odkud.$soubor.'.csv';
  if (!file_exists($data)){
    $this->hlaseni("Soubor $data s daty tabulky nebyl nalezen.");
    return 0;
  }
  if ($tabulka==''){
    /* prazdna tabulka znaci, ze se vyuzije jmeno souboru, ale odstrani se z nej schema */
    //$tabulka=strtolower(str_replace('GF_KOD.','',$soubor));
    if (strpos($soubor,'.')===false){
      $this->hlaseni('Pokud neni uvedeno jmeno tabulky, musi byt prvni soubor vcetne schematu.'); return 0;
    }
    $tabulka=substr($soubor,strpos($soubor,'.')+1);
  }
  
  $f=fopen($data,"r");
  $k=0; 
  $n=0;
  $this->konej('BEGIN');
  while(true){
    $r=fgets($f);
    if ($r===false) break;
    //$r=iconv("windows-1250","utf-8//TRANSLIT",$r);    
    if ($k==0){
      /* prvni radek obsahuje hlavicky */
      $pole=explode($this->oddelovac,$r);
      /* pokud je hlavicka ve tvaru prevedene datumove polozky, pak se bere to, co je za 'as'*/
      for($i=0,$nn=count($pole);$i<$nn;$i++){
        if (preg_match("/^(.+)\s+as\s+(\w+)$/",$pole[$i],$m)){
          $pole[$i]=$m[2];
        }
      }
    }else{
      $hodnoty=explode($this->oddelovac,$r);
      $nenul=true;
      for($i=0;$i<count($hodnoty);$i++){
        if ($hodnoty[$i]=='') {
          $hodnoty[$i]='null'; $nenul=false;
        }else{
          $nenul=true;
        }  
         /* pokud je posledni hodnota na radku numericka a neni uvedena, do promenne hodnoty[i] se dostane x0a */
        if ($i==count($hodnoty)-1 && $hodnoty[$i]==chr(10)){
           $hodnoty[$i]='null';
        } 
        if ($this->conv) $hodnoty[$i]=iconv("windows-1250","utf-8//TRANSLIT",$hodnoty[$i]);
        if ($hodnoty[$i]=='' && $nenul){
           $hodnoty[$i]='"-"'; /*tj. konverze se nepovedla */
           $this->hlaseni('radek '.$k.' hodnotu nelze prevest do UTF-8');
        }   
        if (strstr($hodnoty[$i],'\"')){
          $hodnoty[$i]=str_replace('\"','"||char(34)||"',$hodnoty[$i]);
        }
      }      
      $prikaz="insert into $tabulka (".implode($pole,', ').') values ('.implode($hodnoty,', ').')';
      /*if ($k==9621) {
        echo $prikaz; 
        print_r($hodnoty);
        for ($i=0;$i<$nn;$i++) echo $i,' ',strlen($hodnoty[$i]),' ',implode(unpack("H*", $hodnoty[$i])),"\n";
        print_r($pole);
        break;
      } */
      if ($this->konej($prikaz)){
         $n++;
      }else{
        $this->hlaseni('radek '.$k.' '.$prikaz);
      }
      if ($this->verbose && !($n%100) ) echo "$n\r";       
    }
    $k++;
  }
  $this->konej('COMMIT');
  if (!$this->textOnly){
    $this->hlaseni("Importovano $n zaznamu do $tabulka.");
  }
  return 1;  
}


function konej($prikaz){
  /* bud prikaz vytiskne a nebo vykona SQL prikaz oproti DB vcetne hlaseni chyby */
  if ($this->textOnly){
    echo "$prikaz\n";
  }else{
    $ch=$this->db->Sql($prikaz);
    if ($ch) {
       $this->hlaseni($this->db->Error);
       return false;
    }
  }
  return true;    
}

function proper($s){
  if ($s=='') return '';
  if (@is_numeric($s)) return $s;
  $r=str_replace("\n",' ',$s);
  $r=str_replace(chr(12),' ',$r);
  $r=str_replace(chr(13),' ',$r);
  $r=str_replace(chr(10),' ',$r);
  $r=str_replace(chr(9),' ',$r);
  $r=str_replace("\"",'\"',$r);  /* uvozovky uvnitr retezce jsou uvozeny znakem vyjimky */
  return $r;
} 

function hlaseni($s){
  echo $s."\n";
}

} /*konec definice tridy liteimp */

?>