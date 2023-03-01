<?php
/** Oracle import - funkcionalita importu databazovych tabulek - protikus oraexp
 *  @author Petr Coupek
 *  @date 04.02.2021
 *  11.02.2021
 */

include_once "lib/libdbOracle.php"; 

class ora_imp {

function __construct($napojeni){
  $this->napojeni=$napojeni;  /* prazdny retezec vyvola jen vypis prikazu */
  $this->odkud='export/';
  $this->oddelovac=';';
  $this->verbose=true;  /* vydava echo pri importu */
  $this->textOnly=($napojeni=='');  /* true zpusobi jen tisk prikazu, nejsou vykonany */
  if (!$this->textOnly){
    $a=$this->db = new OpenDB_Oracle($napojeni);
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
  $typy=array();
  while(true){
    $r=fgets($f);
    if ($r===false) break;
    if ($this->conv) {
      $r=iconv("windows-1250","utf-8//TRANSLIT",$r);
    }  
    $r=str_replace(array("\r", "\n"), '', $r);
    if (preg_match("/^table\s+(.*)\($/",$r,$m)){
      $r='create table '.$tabulka.'(';
    }elseif ($r=='primary key ()'){
      $r=''; /* puvodni tabulka byla bez primarniho klice - ignoruj radek 
              * a odstran carku na predchozim radku */
      $prikaz=substr($prikaz, 0, strrpos($prikaz,',  --')). 
              substr($prikaz,strrpos($prikaz,',  --')+1 );
    }else{
      /* poznamenej si typy jednotlivych poli - budou potreba pri importu z CSV */
      if (preg_match("/^(\w+)\s+(.+),/",$r,$m)) {
        $typy[$m[1]]=$m[2];
      }
   
    }    
    $prikaz.=$r."\n";
  }
  if (!$this->konej($prikaz)){  
    $this->hlaseni("Chyba SQL: / ".$prikaz);
    return 0;
  }
  $this->konej('COMMIT');
  fclose($f);
  $f=fopen($data,"r");
  $k=0; 
  $n=0;
  //$this->konej('BEGIN');
  while(true){
    $r=fgets($f);
    
    if ($r===false) break;
    if ($this->conv) {
      $r=iconv("windows-1250","utf-8//TRANSLIT",$r);
    }      
    if ($k==0){
      /* prvni radek obsahuje hlavicky - odstran konec radku */
      $pole=explode($this->oddelovac,str_replace("\n","",$r));
      /* pokud je hlavicka ve tvaru prevedene datumove polozky, pak se bere to, co je za 'as'*/
      for($i=0,$nn=count($pole);$i<$nn;$i++){
        if (preg_match("/^(.+)\s+as\s+(\w+)$/",$pole[$i],$m)){
          $pole[$i]=$m[2];
        }
      }
    }else{
      /* samotna data : odstraneni retezce na konci odradkovani */
      $r=str_replace("\r","",str_replace("\n","",$r));
      $hodnoty=explode($this->oddelovac,$r);
      $nenul=true;
      for($i=0;$i<count($hodnoty);$i++){
        if ($hodnoty[$i]=='') {
          $hodnoty[$i]='null'; $nenul=false;
        }else{
          $nenul=true;
          /* odstraneni dvojitych uvozovek ze zacatku a konce */
          //echo substr($hodnoty[$i],-1)."\n";
          if (substr($hodnoty[$i],0,1)=='"' &&  substr($hodnoty[$i],-1)=='"'){
            $hodnoty[$i]="'".substr($hodnoty[$i],1,-1)."'";
          }
          /* vraceni oddelovace(stredniku) a odradkovani */
          $hodnoty[$i]=str_replace('<sep>',$this->oddelovac,$hodnoty[$i]);
          $hodnoty[$i]=$r=str_replace('<br>',"'||chr(13)||'",$hodnoty[$i]);
          $hodnoty[$i]=$r=str_replace('<chr13>',"'||chr(13)||'",$hodnoty[$i]);
          $hodnoty[$i]=$r=str_replace('<chr12>',"'||chr(12)||'",$hodnoty[$i]);
          $hodnoty[$i]=$r=str_replace('<chr10>',"'||chr(10)||'",$hodnoty[$i]);
          $hodnoty[$i]=$r=str_replace('<chr9>',"'||chr(9)||'",$hodnoty[$i]);
          $hodnoty[$i]=str_replace('\"',"'||chr(34)||'",$hodnoty[$i]);             
        }  
         /* pokud je posledni hodnota na radku numericka a neni uvedena, do promenne hodnoty[i] se dostane x0a */
        if ($i==count($hodnoty)-1 && $hodnoty[$i]==chr(10)){
           $hodnoty[$i]='null';
        } 
        if ($this->conv) {
          $hodnoty[$i]=iconv("windows-1250","utf-8//TRANSLIT",$hodnoty[$i]);
        }  
        if ($hodnoty[$i]=='' && $nenul){
           $hodnoty[$i]='-'; /*tj. konverze se nepovedla */
           $this->hlaseni('radek '.$k.' hodnotu nelze prevest do UTF-8');
        }   
        /*if (strstr($hodnoty[$i],'\"')){
          $hodnoty[$i]=str_replace('\"','"||chr(34)||"',$hodnoty[$i]);
        }*/
        
        
        /* konverze datumovych polozek */
        if ($typy[$pole[$i]]=='DATE'){
          $hodnoty[$i]="to_date(".$hodnoty[$i].",'YYYY-MM-DD HH24:MI:SS')";
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
      if ($this->verbose && !($n%100) ) echo "-- $n\r";       
    }
    $k++;
  }
  $this->konej('COMMIT');
  if (!$this->textOnly){
    $this->hlaseni("Importovano $n zaznamu do $tabulka.");
  }
  return 1;  
}

/** importuje vsechny tabulky, ktere najde v predane slozce
 * @param $odkud string cesta slozky s csv a inf soubory
 */ 

function importuj_schema($odkud){
  /* seznam souboru ve slozce odkud */
  $sou=scandir($odkud);
  for($i=0;$i<count($sou);$i++){
    if (preg_match("/^(\w+)\.inf$/",$sou[$i],$m)){
      //echo "-- soubor: ".$sou[$i],"\n";
      $tab=$m[1]; 
      //echo $shema,$tab;
      $this->hlaseni("Soubor ".$sou[$i]." :");
      $vysledek=$this->importuj($m[1],$tab,$odkud);
      if (!$vysledek){
        $this->hlaseni("$tab - nenahrano");
        break;
      }  
    }else{
      //echo "-- preskakuji: ".$sou[$i],"\n";
    }  
  }
}

/** jen provede ímport dat ze souboru csv do existujici tabulky ve schematu
 * @param $soubor string jmeno imortovanoho souboru (bez cesty k importovane slozce ) 
 * @param $tabulka string prazdna tabulka znaci, ze se vyuzije jmeno souboru, ale odstrani se z nej schema
 * @param $odkud string='' cesta k importovane slozce, pokud neni uvedena, pouzije se vychozi slozka export/ 
*/

function pripoj_csv($soubor,$tabulka,$odkud=''){
  
  if ($odkud!='') $this->odkud=$odkud;
  $data=$this->odkud.$soubor.'.csv';
  if (!file_exists($data)){
    $this->hlaseni("Soubor $data s daty tabulky nebyl nalezen.");
    return 0;
  }
  if ($tabulka==''){
    /* prazdna tabulka znaci, ze se vyuzije jmeno souboru, ale odstrani se z nej schema */
    if (strpos($soubor,'.')===false){
      $this->hlaseni('Pokud neni uvedeno jmeno tabulky, musi byt prvni soubor vcetne schematu.');
      return 0;
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
    $r=str_replace(array("\r", "\n"), '', $r);    
    if ($k==0){
      /* prvni radek obsahuje hlavicky */
      $pole=explode($this->oddelovac,$r);
      /* pokud je hlavicka ve tvaru prevedene datumove polozky, pak se bere to, co je za 'as' */
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
          $hodnoty[$i]='null'; 
          $nenul=false;
        }else{
          $nenul=true;
        }  
         /* pokud je posledni hodnota na radku numericka a neni uvedena, do promenne hodnoty[i] se dostane x0a */
        if ($i==count($hodnoty)-1 && $hodnoty[$i]==chr(10)){
           $hodnoty[$i]='null';
        } 
        if ($this->conv) {
          $hodnoty[$i]=iconv("windows-1250","utf-8//TRANSLIT",$hodnoty[$i]);
        }  
        if ($hodnoty[$i]=='' && $nenul){
           $hodnoty[$i]='"-"'; /*tj. konverze se nepovedla */
           $this->hlaseni('radek '.$k.' hodnotu nelze prevest do UTF-8');
        }   
        if (strstr($hodnoty[$i],'\"')){
          $hodnoty[$i]=str_replace('\"','"||char(34)||"',$hodnoty[$i]);
        }
        if ($hodnoty[$i]!='null') $hodnoty[$i]="'".$hodnoty[$i]."'";
      } 
      if (PHP_VERSION<8){
        $prikaz="insert into $tabulka (".implode($pole,', ').') values ('.implode($hodnoty,', ').')';
      }else{
        $prikaz="insert into $tabulka (".implode(', ',$pole).') values ('.implode(', ',$hodnoty).')';
      }  
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

/** Funkce, ktera umozni upravu nactenych hodnot pred jejich importem
 */ 
function mediator($pole,$hodnoty){

} 


function konej($prikaz){
  /* bud prikaz vytiskne a nebo vykona SQL prikaz oproti DB vcetne hlaseni chyby */
  if ($this->textOnly){
    echo "$prikaz ;\n";
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
  echo "-- ".$s."\n";
}

} /*konec definice tridy liteimp */

?>