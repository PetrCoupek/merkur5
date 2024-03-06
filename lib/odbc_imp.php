<?php
/** SQLite import - funkcionalita importu databazovych tabulek - protikus oraexp
 *  @author Petr Coupek
 *  @date 02.07.2019
 *  je cten csv a inf soubor a po prevodu do UTF8 je to vlozeno do ODBC databaze
 *  16.07.2019 - upravy
 *  24.07.2019 - upravy 
 *  15.01.2020 - upravy
 *  22.01.2020 - import csv
 *  22.06.2023 - upravy
 *  29.01.2024 - upravy
 */


class ODBC_imp {
  var $napojeni,$odkud,$oddelovac,$verbose,$textOnly,$db,$conv,$vytvaret;

function __construct($napojeni){
  $this->napojeni=$napojeni;  /* prazdny retezec vyvola jen vypis prikazu */
  $this->odkud='export/';
  $this->oddelovac=';';
  $this->verbose=true;  /* vydava echo pri importu */
  $this->textOnly=($napojeni=='');  /* true zpusobi jen tisk prikazu, nejsou vykonany */
  if (!$this->textOnly){
    $a=$this->db = new OpenDB_ODBC($napojeni);
  }                                                    
  $this->conv=true;  /* zda provadet konverzi do UTF-8 Pokud FALSE, jiz je zdroj v UTF-8*/  
  $this->vytvaret=true;
}

function __destruct(){
  if (!$this->textOnly){
    $this->db->Close();
  }  
}

/** generovani prikazu create table na zaklade predane struktury
 */ 
function generuj($def){
  $a1=[]; /*pole radku definice entity */
  $a2=[]; /*pole pripojenych poznamek */
  foreach ($def as $k=>$v){
    if (substr($k,0,1)=='-') continue;
    $t=$k.' ';
    if ($v[0]=='VARCHAR' || $v[0]=='VARCHAR2' || $v[0]=='NVARCHAR' || $v[0]=='NVARCHAR2' || $v[0]=='CHAR') {
      if ($v[1]>255) {
        $t.='memo '.$v[2];
      }else{
        if ($v[1]=='') $v[1]=100;
        $t.='text('.$v[1].')'.$v[2];
      }
    }
    if ($v[0]=='CLOB' || $v[0]=='NCLOB' || $v[0]=='ST_GEOMETRY' || $v[0]=='LONG') {
      $t.='memo'.$v[2];
    }
    if ($v[0]=='TIMESTAMP' || $v[0]=='DATETIME' || $v[0]=='DATE' ) {
      $t.='datetime'.$v[2];
    }
    if ($v[0]=='NUMBER' && preg_match("/^(\d+),(\d+)$/",$v[1])){
      $t.='real'.$v[2];
    }
    if ($v[0]=='NUMBER' && preg_match("/^(\d+)$/",$v[1])){
      $t.='integer'.$v[2];
    }
    array_push($a1,$t); 
    array_push($a2,$v[3]);
  }
  if ($def['-primary']=='') {
    /* posledni pridany radek obsahuje carku, tu bude potreba odebrat */
    $a1[count($a1)-1]=substr($a1[count($a1)-1],0,-1);
    $pk='';
  }else{
    $pk="primary key (".$def['-primary'].")";
  }
  /* slepeni poli do viceradkoveho retezce - poznamky v poli $a2 se u ACCESS zahazuji */ 
  for($i=0,$r='';$i<count($a1);$i++){
    $r.=$a1[$i]."\n"; 
  }
  
  $r="create table ".$def['-table']."(\n".$r.$pk.")"; 
  return $r;
}


/** importuje jednu entitu ze souboru .inf a souboru .csv
 * @param $soubor - jmeno souboru, bud SCHEMA.TABULKA nebo TABULKA
 * @param $tabulka - jmeno tabulky, pokud je prazdny retezec, ma se za to, ze je soucasni jmena souboru SCHEMA.TABULKA
 * @param $odkud - $dokud je slozka, ve ktere se soubor nachazi
 */ 
function importuj($soubor,$tabulka,$odkud=''){
  /* provede import tabulky do schematu */
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
  $def=[];
  while(true){
    $r=fgets($f);
    if ($r===false) break;
    $r=str_replace(["\r", "\n"], '', $r);
    if (substr($r,0,3)==' --' || substr($r,0,2)=='--' ) continue; /* pozn. k ent se zahodi */
    if (preg_match("/^table\s+(.*)\($/",$r,$m)){
      $def['-table']=$m[1];
    }elseif($r=='primary key ()'){
      $def['-primary']='';
    }elseif( preg_match("/primary key\s*\((.*)\)/",$r,$m) ){
      $def['-primary']=$m[1];
    }elseif($r==')'){
      /* konec tabulky, nedelej nic */    
    }else{
      /* parsovani definice tabulky */
      /* parsovani definice tabulky */
      if (preg_match("/^(\w+) (\w+)\((\d+)\)( NOT NULL,|,) (.*)$/",$r,$m)){
        $def[$m[1]]=[$m[2],$m[3],$m[4],$m[5]];
      }elseif (preg_match("/^(\w+) (\w+)\((\d+),(\d+)\)( NOT NULL,|,) (.*)$/",$r,$m)){
        $def[$m[1]]=[$m[2],$m[3].','.$m[4],$m[5],$m[6]];
      }elseif (preg_match("/^(\w+) (\w+)\((\d+)\)\((\d+)\)( NOT NULL,|,) (.*)$/",$r,$m)){
        $def[$m[1]]=[$m[2],$m[3].','.$m[4],$m[5],$m[6]];
      }elseif (preg_match("/^(\w+) (\w+)( NOT NULL,|,) (.*)$/",$r,$m)){
        $def[$m[1]]=[$m[2],'',$m[3],$m[4]];
      }else{
        echo "! neni: - ".$r."\n";
      }
    }   
  }
  $prikaz=$this->generuj($def);
  //echo "$prikaz";   
  if ($this->vytvaret) if (!$this->konej($prikaz)){  
    $this->hlaseni("Chyba SQL: / ".$prikaz);
    return 0;
  }
  fclose($f);
  $f=fopen($data,"r");
  $k=0; 
  $n=0;
  $pole=[];
  while(true){
    $r=fgets($f);
    if ($r===false) break;
    $r=str_replace(array("\r", "\n"), '', $r);
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
        $type=$def[$pole[$i]][0];
        //echo 'Type='.$type; 
        if ($hodnoty[$i]=='' || ($hodnoty[$i]=='""' && ($type=='TIMESTAMP' || $type=='DATETIME' || $type=='DATE'))) {
          $hodnoty[$i]='null'; 
          $nenul=false;
        }else{
          $nenul=true;
        } 
        /* pro ODBC Access je treba prehodit dvojite uvozovky obsahujici retezce za jednoduche
         * test, zda atribut zacina a konci dvojitymi uvozovkami 
         */ 
        if (substr($hodnoty[$i],0,1)=='"' && substr($hodnoty[$i],-1,1)=='"'){
          $vnitrek=substr($hodnoty[$i],1,-1);
          /* pokud jsou uvnitr textu jednoduche uvozovky, je potreba zmenit je do insertu 
           * za poslouponost '+chr(39)+' 
           */
          $vnitrek=str_replace("'","'+chr(39)+'",$vnitrek);    
          $hodnoty[$i]="'".$vnitrek."'";
        }
        /* pro vychozi typ TIMESTAMP je treba prehodit tvar vstupniho datoveho vstupniho pole, aby je Access nacetl */
        if ($type=='TIMESTAMP'){
          if(preg_match("/(\d{2})\.(\d{2})\.(\d{2}) (\d{2}):(\d{2}):(\d{2})/",$hodnoty[$i],$m)){
            $hodnoty[$i]="'20".$m[3].'-'.$m[2]."-".$m[1]." ".$m[4].":".$m[5].":".$m[6]."'";
          }
          if(preg_match("/(\d{2})\.(\d{2})\.(\d{4}) (\d{2}):(\d{2}):(\d{2})/",$hodnoty[$i],$m)){
            $hodnoty[$i]="'".$m[3].'-'.$m[2]."-".$m[1]." ".$m[4].":".$m[5].":".$m[6]."'";
          }
        }
        
        
        /* pokud je posledni hodnota na radku numericka a neni uvedena, do promenne hodnoty[i] se dostane x0a */
        if ($i==count($hodnoty)-1 && $hodnoty[$i]==chr(10)){
           $hodnoty[$i]='null';
        } 
        //if ($this->conv) $hodnoty[$i]=iconv("utf-8//TRANSLIT","windows-1250",$hodnoty[$i]);
        
        //if ($hodnoty[$i]=='' && $nenul){
        //   $hodnoty[$i]='"-"'; /*tj. konverze se nepovedla */
        //   $this->hlaseni('radek '.$k.' hodnotu nelze prevest do UTF-8');
        //}   
        
      }
     
      $prikaz="insert into $tabulka (".implode(', ',$pole).') values ('.implode(', ',$hodnoty).')';
      if ($this->konej($prikaz)){
         $n++;
      }else{
        //$this->hlaseni('radek '.$k.' '.$prikaz);
        //return 0;
      }
      if ($this->verbose && !($n%100) ) echo "$n\r";       
    }
    $k++;
  }
  
  if (!$this->textOnly){
    $this->hlaseni("Importovano $n zaznamu do $tabulka.");
  }
  return 1;  
}

/** Importuje vsechny soubory v dane slozce - predpoklada se, ze obsahuje relacni schema
 *  @param - vstupni slozka, ktra obsahuje soubory ve tvaru SCHEMA.TABULKA.inf
 */ 
function importuj_schema($odkud){
  if (!is_dir($odkud)) {
    echo $odkud." není složka\n";
    return 0;
  }  
  $sou=scandir($odkud); /*seznam souboru ve slozce odkud*/
  $n=0; /* obsahuje pocet skutecne importovanych taulek */
  for($i=0;$i<count($sou);$i++){
    if (preg_match("/^(\w+)\.(\w+)\.inf$/",$sou[$i],$m)){
      $schema=$m[1]; 
      $tab=$m[2];
      $this->hlaseni("Soubor ".$sou[$i]." :");
      $vysledek=$this->importuj($schema.'.'.$tab,$tab,$odkud);
      if (!$vysledek){
        $this->hlaseni("$tab - nenahrano");
        break;
      }
      $n++;  
    }  
  }
  if ($n==0) echo "Varování: ze slozky $odkud nebylo nic importováno. \n".
   "Ověřte, zda jména INF souborů jsou ve tvaru SCHEMA.TABULKA.inf";
}

function pripoj_csv($soubor,$tabulka,$odkud=''){
  /* jen provede import dat ze souboru csv do existujici tabulky ve schematu */
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
  $pole=[];
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
      $prikaz="insert into $tabulka (".implode(', ',$pole).') values ('.implode(', ',$hodnoty).')';
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