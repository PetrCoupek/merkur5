<?php
/** Database wrapper . Abstract core
 *  Abstract class
 *  @author Petr Čoupek
 */ 
     
 /*  2014-2022
 */
 
abstract class OpenDB {
  var $conn;      // pripojeni - vysledek po volani ocilogon
  var $parse;     // dotaz sql - vysledek ociparse
  var $data;      // struktura, ve ktere je radek z databaze
  var $stav;      // stav po selhani SQL dotazu
  var $p_sloupcu; // pocet sloupcu
  var $com_kontr; // kontrola zda je zapnuty commit
  var $Error;     // retezec obsahujici chybu SQL. (kod, popis, ofset)..
  //var $charset="EE8MSWIN1250";
  var $charset="AL32UTF8";
  var $commit=true;
  var $typedb='';
  //var $charset="UTF-8";
  //var $utf8=false; /* zde bude probihat konverze dat do a z UTF-8 */
  
  /** $db = new OpenDB_Oracle($connection_string)
   * 
   * connect to the dabasase, if database does not exist, it will be made a creation attempt 
   * @param string $connect - connection string
   * @return OpenDB_Oracle a new database wrapper object, or false when connection was not established
   */
  public function __construct($connect){ 
   
  } 
  
  /** $error = $db->Sql($sql_command)
   * 
   * Provide a SQL command in the target database 
   * @param string $command - and sql command
   * @param array $bind - list of bind parameters
   * @return boolean, true when an error has occured, false on success
   */
  public function Sql($command,$bind=array()){
    
  }
  
  
  /** $result = $db->FetchRow();
   * 
   * Provide fetch of one row of the data from the database table to the local Hash
   * @return boolean, true when next row has been fetched, false at the end of data
   */  
  public function FetchRow(){
    
  }
  
  /** $error = $db->Pragma("table_info('TABLE_NAME'");
   * 
   * Provide special non- standartized task with the database - supported is table_info pragma 
   * @param string $command - table info pragma
   * @return boolean, true when an error has occured, false on success
   */
  public function Pragma($dotaz){
  
  }
  
  /** $result = $db->FetchRowA();
   * 
   * Provide fetch of one row of the data from the database table to the local Array
   * @return boolean, true when next row has been fetched, false at the end of data
   */ 
  public function FetchRowA(){
    
  }

  /** $db->Close();
   * 
   * It closes the database connection
   */  
  public function Close(){
    
  }
  
  /** $value = $db->Data('attribute');
   * 
   * This method returns current attribute value
   * @param string $attribute - the name of the attribute (in the view/table), 
   *   automatic case sensitivity detection 
   * @return string with the attribute value
   */
  function Data($sloupec){
    if (isset($this->data[$sloupec])) {
      return $this->data[$sloupec];
    }else{
      return '';
    }
  }
  
  /** $value = $db->DataHash();
   * 
   * This method returns current attribute value
   * @return hash with the current fetched row values BLOB are converted to strings.
   */
  function DataHash(){
    $h=array();
    if ($this->data) foreach ($this->data as $k=>$v){
      $h[$k]=isset($this->data[$k])?(
       (gettype($this->data[$k])=="string")?$v:$this->data[$k]->load()
       ):'';      
    }
    return $h;  
  }  
 
  
  /** $string = $db->SqlFetch($sql_command)
   * 
   * combine Sql and FetchRow method into one step and returns data hash 
   * @param string $command - and sql command
   * @param array $bind - list of bind parameters
   * @return string with the data content
   */
  function SqlFetch($prikaz,$bind=array()){
    /* zjednoduseni nacteni hodnoty z db primo do promenne */
    if (!$this->Sql($prikaz,$bind) && $this->FetchRowA()) {
      return (string)($this->data[0]);
    }else{
      return '';
    }  
  }

  /** $string = $db->SqlFetchRow($sql_command)
   * 
   * combine Sql and FetchRow method into one step and returns data hash 
   * @param string $command - and sql command
   * @param array $bind - list of bind parameters
   * @return array result Data hash or empty array
   */
  function SqlFetchRow($prikaz,$bind=array()){
    /* zjednoduseni nacteni hodnoty z db primo do promenne */
    if (!$this->Sql($prikaz,$bind) && $this->FetchRow()) {
      return $this->data;
    }else{
      return array();
    }  
  }
  
  /** $array = $db->SqlFetchArray($sql_command,$limit=0)
   * 
   * combine Sql and FetchRow method into one step and returns data array
   * @param string $sql_command - and sql command
   * @param array $bind - list of bind parameters
   * @param integer $limit - max. count of resuts , 0= no limit
   * @return array with the data content
   */
  function SqlFetchArray($prikaz,$bind=array(),$limit=0){
    /* zjednoduseni nacteni celeho vysledku select primo do pole v PHP s volitelnym limitem */
    $a=array();
    //$a= new SplFixedArray(10000);$i=0;
    if (!$this->Sql($prikaz,$bind)){
      while ($this->FetchRow()){
        array_push($a,$this->DataHash());
        //$a[++$i]=$this->DataHash();
        if ($limit && $limit<=count($a)) break;
      }
    }
    return $a;    
  }
  
  /** $error = $db->SqlFetchKeys($sql_command,$key)
   * 
   * combine Sql and FetchRow method into one step and returns data array
   * @param string $sql_command - and sql command
   * @return array with the data content
   */
  function SqlFetchKeys($prikaz,$key,$bind=array()){
    /* zjednoduseni nacteni celeho vysledku select primo do pole podle klice */
    $a=array();
    if (!$this->Sql($prikaz,$bind)){
      while ($this->FetchRow()){
        if (isset($a[$this->Data($key)])){
          /* tato hodnota klice se opakuje, struktura bude pole */
          if (!isset($a[$this->Data($key)][0]) ){
            /* pole zatim neexistuje, vlozeni jiz zarazeneho prvku do pole */
            $tmp=$a[$this->Data($key)];
            $a[$this->Data($key)]=array();
            array_push($a[$this->Data($key)],$tmp);
          }
          /* pripojeni prvku k poli */
          array_push($a[$this->Data($key)],$this->DataHash());  
        }else{
          $a[$this->Data($key)]=$this->DataHash();
        }  
      }
    }
    return $a;    
  }
  
  /** $result = $db->SqlFetchList($prikaz,$limit,$sep,$bind)
   * 
   * prepare a list of values from a select query to one column
   * @param string $sql_command - and sql command
   * @param arry $bind
   * @return string a result list or empty string ( also in case of error )
   */
  function SqlFetchList($prikaz,$bind=array(),$limit=100,$sep=', '){
    $r='';
    if (!$this->Sql($prikaz,$bind)) {    
      while ($this->FetchRowA() && $limit--){
        $r.=($r==''?'':$sep).(string)($this->data[0]);
      } 
    }  
    return $r;  
  }
}
 
?>