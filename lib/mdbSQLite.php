<?php
/**  Database wrapper for SQLite 3 database in Microbe
 *  
 *  @author Petr Čoupek
 */  
 
 /*  29.10.2014-2021
  * 21.06.2023 - 
  */
include_once "mdbAbstract.php";
 
class OpenDB_SQLite extends OpenDB{
  var $conn;       /* pripojeni - vysledek po volani objektu SQLite3 */
  var $parse;      /* dotaz sql - SQLite3 objekt, ktery (zpravidla) obsahuje vysledek dotazu */
  var $data;       /* struktura, ve ktere je radek z databaze */
  var $stav;       /* stav po selhani SQL dotazu */
  var $p_sloupcu;  /* pocet sloupcu */
  var $com_kontr;  /* kontrola zda je zapnuty commit */
  var $Error;      /* retezec obsahujici chybu SQL. (kod, popis, ofset).. */
  var $typedb;
  var $prepare;
  
  /** $db= new OpenDB_SQLite($connection_string)
   * 
   * connect to the dabasase, if database does not exist, it will be made a creation attempt 
   * @param string $connect - connection string
   * @return a new database wrapper object, or false when connection was not established
   */
  function __construct($connect){
    /* konstruktor, ktery vytvori pripojeni do DB a nebo da chybu - tu tiskne htpr */
    $this->typedb='sqlite';
    $m=array();
    if (preg_match('/^file=(.+)\,mode=(.+)$/i',$connect,$m)){
      if ($m[2]==1){
        if (is_file($m[1])){
          $this->conn=new \SQLite3($m[1],SQLITE3_OPEN_READWRITE);
        }else{
          /* v pripade neexistence souboru s databazi dojde k zalozeni prazdne DB */
          $this->conn=new \SQLite3($m[1],SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
        }  
      }else{
        $this->conn=new \SQLite3($m[1],SQLITE3_OPEN_READONLY);
      }  
      $this->com_kontr=true; /* nastav natrue autocomit je implicitne zaply */
      if (!$this->conn){
        $this->Error='SQLite connect failed.';
        htpr($this->Error);
        $this->stav=false;              
        return $this->stav;              
      }else{
        $this->stav=true;
        $this->Error='';
        //$this->conn->enableExceptions(true);
        $this->conn->enableExceptions(false);
        return $this->stav; 
      }
    }else{
      $this->Error='Incorrect connect string.';
      htpr($this->Error);
      $this->stav=false;              
      return $this->stav; 
    }  
  }
  
  /** $error = $db->Sql($sql_command)
   * 
   * Provide 
   * @param string $command - and sql command
   * @param array $bind - list of bind parameters
   * @return boolean, true when an error has occured, false on success
   */
  public function Sql($command,$bind=array()){
    $errorCode=0;
    $errorReportingLevel = error_reporting(); /* poznamena uroven vypisovani chyb*/
    //error_reporting(0);
    $this->prepare=$this->conn->prepare($command);
    if (!$this->prepare){
      $this->conn->lastErrorCode()!=0?
      ($this->conn->lastErrorCode().": ".$this->conn->lastErrorMsg()):'příkaz SQL nebyl zpracován';        
      $this->stav=true;
      error_reporting($errorReportingLevel); /* vraci uroven vypisovani chyb */
      return $this->stav;
    }
    if (count($bind)){
      foreach($bind as $k=>$v){
        $this->prepare->bindValue($k,$bind[$k], 
         is_null($bind[$k])?SQLITE3_NULL:
          (is_int($bind[$k])?SQLITE3_INTEGER:
            (is_float($bind[$k])?SQLITE3_FLOAT:SQLITE3_TEXT))); /* pozor $v nefunguje! musi byt $bind[$k] !*/
      }
    }
    try{
      $this->parse=$this->prepare->execute();
      $errorCode = $this->conn->lastErrorCode();  
    } catch (Exception $e){
      $errorCode = 1;  
    }  
    
    error_reporting($errorReportingLevel); /* vraci uroven vypisovani chyb */
     
    if(!$this->parse || $errorCode>0){
      //$em=array("[$command]",$this->conn->lastErrorCode());
      $this->Error=$this->conn->lastErrorCode()!=0?
       ($this->conn->lastErrorCode().": ".$this->conn->lastErrorMsg()):'příkaz SQL nebyl zpracován';        
      $this->stav=true;
      return $this->stav;
    }else{
      /* probehlo bez problemu */
      //$this->p_sloupcu=count($this->parse); 
      $this->stav=false;
      $this->Error='';
      return $this->stav;
    }
  }
  
  /** $error = $db->Pragma("table_info('TABLE_NAME'");
   * 
   * Provide special non- standartized task with the database - supported is table_info pragma 
   * @param string $command - table info pragma
   * @return boolean, true when an error has occured, false on success
   */
  function Pragma($command){
    /* metoda vraci strukturu s udaji - napr. struktura tabulky a nebo false v pripade chyby*/
    /* duvodem teto metody je sjednoceni pristupu k datovemu katalogu napric databazemi */
     $m=array(); 
     $struktura=array();
     if (preg_match('/^\s*table_info\(\'(\w+)\'\)\s*$/',$command,$m)){
       $table_name=$m[1];
       /* vrat strukturu tabulky $m[1]*/
       if (!($this->parse=$this->conn->query("pragma table_info('$table_name')"))) return false;
       while($this->data=$this->parse->fetchArray(SQLITE3_ASSOC)){
          $struktura[$this->data['cid']]=array( /* pole indexovane podle hodnoty cid 0,1,..*/
           'name'=>$this->data['name'],
           'comment'=>$this->data['name'],
           'type'=>$this->data['type'],
           'notnull'=>$this->data['notnull'],
           'default'=>$this->data['dflt_value'],
           'pk'=>$this->data['pk'],
           'datename'=>$this->data['name'] /* SQLITe nema datetime typ - 
              jinak vhodny datovy format atributu name pro select -dopln v pripade typu datetime]" */
           );
       }
       return ($struktura); 
     }
     if (preg_match('/^\s*catalog\s*$/',$command,$m)){
       /* vraci seznam tabulek - pohledu */
       if (!($this->parse=$this->conn->query("select * from sqlite_master order by name asc"))) return false;
       $n=0;
       while($this->data=$this->parse->fetchArray(SQLITE3_ASSOC)){
         if (($this->data['type']=='table' || $this->data['type']=='view') 
              && !str_contains($this->data['sql'],'VIRTUAL TABLE' ))
           $struktura[$n++]=array(
            'name'=>$this->data['name'],
            'type'=>$this->data['type'],
            'tbl_name'=> $this->data['tbl_name'],
            'sql'=>$this->data['sql']          
         );       
       }
       return ($struktura);
     }
     $this->stav=false;
     return($this->stav);
  }  
 
  /** $result = $db->FetchRow();
   * 
   * Provide fetch of one row of the data from the database table to the local Hash
   * @return boolean, true when next row has been fetched, false at the end of data
   */  
  function FetchRow(){
    /* pritahovani vet - asoc pole - nepracuje v pripade chyby prikazu insert */
    if (is_object($this->parse)) {
      try{
        if($this->data=$this->parse->fetchArray(SQLITE3_ASSOC)){
          /* podminka if neni splnena na konci datasetu - this data se naplni vzdy */
          //print_r($this->data);
          return true;
        }else{
          return false;
        }
      }catch (Exception $e){
        $this->Error=$e->getMessage();
        return false;
      }
    }
    return false;    
  }
  
  /** $result = $db->FetchRowA();
   * 
   * Provide fetch of one row of the data from the database table to the local Array
   * @return boolean, true when next row has been fetched, false at the end of data
   */ 
  function FetchRowA(){
    /* pritahovani dat - jako pole */
    if ($this->parse) try{
      if($this->data=$this->parse->fetchArray(SQLITE3_NUM)){
        return $this->data;
      }else{
        return false;
      }
    }catch (Exception $e){
      $this->Error=$e->getMessage();
      return false;
    }
    return false;     
  }
    
  /** $value = $db->Data('attribute');
   * 
   * This method returns current attribute value
   * @param string $attribute - the name of the attribute (in the view/table), 
   *   automatic case sensitivity detection 
   * @return string with the attribute value
   */
  function Data($sloupec){
    /* vraceni dat resi case sensitivitu */
    if (isset($this->data[$sloupec])) {
      return $this->data[$sloupec];
    }elseif (isset($this->data[strtoupper($sloupec)])){
      return $this->data[strtoupper($sloupec)];
    }elseif (isset($this->data[strtolower($sloupec)])){
      return $this->data[strtolower($sloupec)];
    }else{
      return '';
    }
  }
  
  /** $value = $db->DataHash();
   * 
   * This method returns current attribute value
   * @return hash with the current fetched row values
   */
  function DataHash(){
    return (array)$this->data;
  }  
 
  /** $db->Close();
   * 
   * Closes the database connection
   */  
  function Close(){
    if ($this->conn) {$this->conn->close();}
  }
}
 
?>
