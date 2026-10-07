<?php
/** SQLite import - funkcionalita importu databazovych tabulek - protikus oraexp
 *  @author Petr Coupek
 *  @date 02.07.2019
 *  je cten csv a inf soubor a po prevodu do UTF8 je to vlozeno do SQLITE databaze
 *  16.07.2019 - upravy
 *  24.07.2019 - upravy 
 *  15.01.2020 - upravy
 *  22.01.2020 - import csv
 *  22.06.2023 - upravy
 *  29.01.2024 - upravy
 *  08.02.2024 - zmena generovani cilove tabulky
 *  25.06.2025 - pokud neni csv soubor, jen se provede zalozeni tabulky
 *  15.07.2025 - zaveden parametr bez dat, takze je jen zalozena tabulka
 */


class Lite_imp {
  var $napojeni,
      $odkud,
      $oddelovac,
      $verbose,
      $textOnly,
      $db,
      $conv,
      $create;

function __construct($napojeni,$pars=[]){
  $this->napojeni=$napojeni;  /* prazdny retezec vyvola jen vypis prikazu */
  $this->odkud='export/';
  $this->oddelovac=';';
  $this->verbose=true;  /* vydava echo pri importu */
  $this->textOnly=($napojeni=='');  /* true zpusobi jen tisk prikazu, nejsou vykonany */
  if (!$this->textOnly){
    $this->db = new OpenDB_SQLite($napojeni);
  }                                                    
  $this->conv=isset($pars['conv'])&&$pars['conv'];  /* zda provadet konverzi do UTF-8 Pokud FALSE, jiz je zdroj v UTF-8*/  
  $this->create=isset($pars['create'])&&$pars['create'];
}

function __destruct(){
  if (!$this->textOnly){
    $this->db->Close();
  }  
}

/** generovani DDL SQL příkazu create table na základě předané struktury
 */ 
function generuj($def){
  $a1=[]; /*pole radku definice entity */
  $a2=[]; /*pole pripojenych poznamek */
  foreach ($def as $k=>$v){
    if (substr($k,0,1)=='-') continue;
    $t=$k.' ';
    if ($v[0]=='VARCHAR' || $v[0]=='VARCHAR2' || $v[0]=='NVARCHAR' || $v[0]=='NVARCHAR2' || $v[0]=='CHAR') {
      //if ($v[1]>255) {
      //  $t.='text '.$v[2];
      //}else{
      //  if ($v[1]=='') $v[1]=100;
        $t.='text('.$v[1].')'.$v[2];
      //}
    }
    if ($v[0]=='CLOB' || $v[0]=='NCLOB' || $v[0]=='ST_GEOMETRY' || $v[0]=='LONG') {
      $t.='text'.$v[2];
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
  /* slepeni poli definice a poznamky do viceradkoveho retezce */ 
  for($i=0,$r='';$i<count($a1);$i++){
    $r.=$a1[$i].$a2[$i]."\n"; 
  }
  if (isset($def['-comment'])){
    $comment=$def['-comment']."\n";
  }else{
    $comment='';
  }
  $r="create table ".$def['-table']."(\n".$comment.$r.$pk.")"; 
  return $r;
}


/** importuje jednu entitu ze souboru .inf a souboru .csv do schematu
 * @param string $soubor - jmeno souboru, zpravidla shodne se jmenem tabulby
 * @param string $tabulka - jmeno tabulky, pokud neni uvedeno, predpoklada se jmeno souboru 
 *                ve tvaru SCHEMA.TABLE
 * @param string $odkud - $dokud je slozka, ve ktere se soubor nachazi
 */ 
function importuj($soubor,$tabulka,$odkud='',$bez_dat=false){
  if ($odkud!='') $this->odkud=$odkud;
  echo "$soubor $odkud $tabulka\n";
  // Speed up import: disable synchronous and set journal to memory
  if (!$this->textOnly) {
    $this->db->Sql('PRAGMA synchronous = OFF');
    $this->db->Sql('PRAGMA journal_mode = MEMORY');
    $this->db->Sql('PRAGMA foreign_keys = OFF');
  }

  /* definicni soubor, datovy soubor */
  $info=$this->odkud.$soubor.'.inf';
  $data=$this->odkud.$soubor.'.csv';
  if (!file_exists($info)){
    $this->hlaseni("Soubor $info s definici struktury tabulky nebyl nalezen.");
    return 0;
  }
  if (!file_exists($data)){
    $this->hlaseni("Soubor $data s daty tabulky nebyl nalezen.");
    //return 0;
  }
  if ($tabulka==''){
    /* prazdna tabulka znaci, ze se vyuzije jmeno souboru, ale odstrani se z nej schema */
    //$tabulka=strtolower(str_replace('GF_KOD.','',$soubor));
    if (strpos($soubor,'.')===false){
      $this->hlaseni('Pokud neni uvedeno jmeno tabulky, musi byt prvni soubor vcetne schematu.'); return 0;
    }
    $tabulka=substr($soubor,strpos($soubor,'.')+1);
  }
  //$tabulka=substr($soubor,strpos($soubor,'.')+1);
  
  $f=fopen($info,"r");
  $prikaz='';
  $def=[];
  while(true){
    $r=fgets($f);
    if ($r===false) break;
    $r=str_replace(["\r", "\n"], '', $r);
    if ($this->conv) $r=iconv("windows-1250","utf-8//TRANSLIT",$r);
    if (substr($r,0,3)==' --' || substr($r,0,2)=='--' ) {
      /* poznamka k entite */
      $def['-comment']=$r;
      continue;
    } 
    if (preg_match("/^table\s+(.*)\($/",$r,$m)){
      if (strpos($m[1],'.') > 0) { 
        $def['-table']=substr($m[1],strpos($m[1],'.')+1); /* odstranění případné tečky a jména schematu v SCHEMA.TABLE názvu */
      } else {
        $def['-table']=$m[1];
      }  
    }elseif($r=='primary key ()'){
      $def['-primary']='';
    }elseif( preg_match("/primary key\s*\((.*)\)/",$r,$m) ){
      $def['-primary']=$m[1];
    }elseif($r==')'){
      /* konec tabulky, nedelej nic */  
    }else{
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
  echo "$def\n";
  $prikaz=$this->generuj($def);
  //echo "$prikaz\n---------\n";
  //return 0;
  
  $this->konej('BEGIN');
  if (!$this->konej($prikaz)){  
    $this->hlaseni("Chyba SQL: / ".$prikaz);
    $this->konej('COMMIT');
    return 0;
  }
  $this->konej('COMMIT');
  fclose($f);
  if (!file_exists($data) || $bez_dat) return 0;
  $f=fopen($data,"r");
  $k=0; 
  $n=0;
  $this->konej('BEGIN');
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
        $type=$def[$pole[$i]][0];
        if ($hodnoty[$i]=='' || ($hodnoty[$i]=='""' && ($type=='TIMESTAMP' || $type=='DATETIME' || $type=='DATE'))) {
          $hodnoty[$i]='null'; 
          $nenul=false;
        }else{
          $nenul=true;
        }  
         /* pokud je posledni hodnota na radku numericka a neni uvedena, 
          * do promenne hodnoty[i] se dostane x0a */
        if ($i==count($hodnoty)-1 && $hodnoty[$i]==chr(10)){
           $hodnoty[$i]='null';
        } 

        if ($this->conv) $hodnoty[$i]=iconv("windows-1250","utf-8//TRANSLIT",$hodnoty[$i]);

        if ($hodnoty[$i]=='' && $nenul){
           $hodnoty[$i]='"-"'; /*tj. konverze se nepovedla */
           $this->hlaseni('radek '.$k.' hodnotu nelze prevest do UTF-8');
        }   
        
        /* uvozovky uvnitr textu nahrad posloupnosti s funkci chr - jen pro textovy vypis */
        if ($this->textOnly && strstr($hodnoty[$i],'\"')){
          $hodnoty[$i]=str_replace('\"','"||char(34)||"',$hodnoty[$i]);
        }

        /* pro vychozi typ TIMESTAMP je treba prehodit tvar vstupniho datoveho vstupniho pole, aby slo o datum */
        if ($type=='TIMESTAMP'){
          if(preg_match("/(\d{2})\.(\d{2})\.(\d{2}) (\d{2}):(\d{2}):(\d{2})/",$hodnoty[$i],$m)){
            $hodnoty[$i]="'20".$m[3].'-'.$m[2]."-".$m[1]." ".$m[4].":".$m[5].":".$m[6]."'";
          }
          if(preg_match("/(\d{2})\.(\d{2})\.(\d{4}) (\d{2}):(\d{2}):(\d{2})/",$hodnoty[$i],$m)){
            $hodnoty[$i]="'".$m[3].'-'.$m[2]."-".$m[1]." ".$m[4].":".$m[5].":".$m[6]."'";
          }
        }
        

      }
     
      if ($this->textOnly){
        $prikaz="insert into $tabulka (".implode(', ',$pole).') values ('.implode(', ',$hodnoty).')';
        $this->konej($prikaz);
        $n++;
      }else{
        /* skutecna manipulace s DB - hodnoty se vazou pres pole $bind metody Sql */
        $placeholdery=[];
        $bind=[];
        for($j=0;$j<count($hodnoty);$j++){
          $klic=':v'.$j;
          $placeholdery[]=$klic;
          $val=rtrim($hodnoty[$j],"\r\n");
          if ($val==='null'){
            $bind[$klic]=null;
          }elseif (strlen($val)>=2 && $val[0]==='"' && $val[strlen($val)-1]==='"'){
            /* text v uvozovkach - odstraneni obalu a zdvojenych uvozovek */
            $bind[$klic]=str_replace('""','"',substr($val,1,-1));
          }elseif (strlen($val)>=2 && $val[0]==="'" && $val[strlen($val)-1]==="'"){
            /* datum v apostrofech (prevedeny TIMESTAMP) */
            $bind[$klic]=substr($val,1,-1);
          }else{
            $bind[$klic]=$val;
          }
        }
        $prikaz="insert into $tabulka (".implode(', ',$pole).') values ('.implode(', ',$placeholdery).')';
        if (!$this->db->Sql($prikaz,$bind)){
           $n++;
        }else{
          $this->hlaseni('radek '.$k.' '.$this->db->Error.' / '.$prikaz);
        }
      }
      if ($this->verbose && !($n%100) ) echo "$n\r";       
    }
    $k++;
  }
  $this->konej('COMMIT');
  if (!$this->textOnly){
    $this->hlaseni("Importovano $n zaznamu do $tabulka.");
    // Restore PRAGMAs if needed
    $this->db->Sql('PRAGMA synchronous = FULL');
    $this->db->Sql('PRAGMA journal_mode = DELETE');
    $this->db->Sql('PRAGMA foreign_keys = ON');
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
  /* jen provede �mport dat ze souboru csv do existujici tabulky ve schematu */
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
  //$r=str_replace("\"",'\"',$r);  /* uvozovky uvnitr retezce jsou uvozeny znakem vyjimky */
  //$r=str_replace('"', '""', $r);  /* uvozovky uvnitr retezce jsou zdvojeny */
  return $r;
} 

function hlaseni($s){
  echo $s."\n";
}

/* When writing CSV data*/
function escapeCsvField($field) {
    /* If field contains quotes, it needs to be quoted */
    if (strpos($field, '"') !== false ) {
        /* Escape quotes by doubling them */
        $field = str_replace('"', '""', $field);
        /* Wrap in quotes */
        return '"' . $field . '"';
    }
    return $field;
}

} /*konec definice tridy liteimp */

?>