<?php
/**  Database wrapper for ODBC connected database 
 *  
 *  @author Petr Coupek
 *  29.10.2014, 22.1.2019, 08.01.2024
 */

include_once "mdbAbstract.php";
 
class OpenDB_ODBC extends OpenDb{
  var $conn;       /* pripojeni - vysledek po volani odbc_connect */
  var $parse;      /* dotaz sql - vysledek odbc_exec */
  var $data;       /* struktura, ve ktere je radek z databaze */
  var $stav;       /* stav po selhani SQL dotazu */
  var $p_sloupcu;  /* pocet sloupcu */
  var $com_kontr;  /* kontrola zda je zapnuty commit */
  var $Error;      /* retezec obsahujici chybu SQL. (kod, popis, ofset) */
  
  /** $db= new OpenDB_ODBC($connection_string)
   * 
   * connect to the dabasase, if database does not exist, it will be made a creation attempt 
   * @param string $connect - connection string
   * @return a new database wrapper object, or false when connection was not established
   */
  function __construct($napojeni){
    $m=array();
    if (preg_match('/^DSN=(.+);DBQ=(.*)$/i',$napojeni,$m)){
      $this->conn=@odbc_connect('Driver={Microsoft Access Driver (*.mdb)};'.
      $napojeni,'','');
      $this->com_kontr=true; /* nastav natrue autocomit - je implicitne zaply */
      if (!$this->conn){
        $this->Error='ODBC connect '.$napojeni.' failed. ';
        htpr($this->Error);
        $this->stav=false;              
        return $this->stav;              
      }else{
        $this->stav=true;
        $this->Error='';  
        return $this->stav;
      }
    }else{
      $this->Error='Incorrect connect string.';
      htpr($this->Error);
      $this->stav=false;              
      return $this->stav; 
    }  
  }

  /** $error = $db->Sql($sql_command, $bind)
   * 
   * Provide 
   * @param string $command - sql command
   * @param array $bind - bind parametres, processed in this method
   * @return boolean, true when an error has occured, false on success
   */
  function Sql($dotaz, $bind=[]){
    /* pro ODBC zdroje je tu emulace nekterych pragma prikazu, aby se to podobalo chovani SQLite */
    $m=array();
    $dotaz=iconv("utf-8","windows-1250",$dotaz);
    //deb($dotaz);
  
    if (preg_match('/^\s*pragma\s+(.+)$/',$dotaz,$m)){
      return $this->Pragma($dotaz,$m[1]);
    }
    /* manually process the bind parameters, if any */
    if (count($bind)){
      foreach ($bind as $k=>$v){
        if (!is_numeric($v)) $v="'".str_replace("'","\\'",$v)."'";
        $dotaz=str_replace($k,$v,$dotaz);
      }  
    }
    $this->parse=@odbc_exec($this->conn,$dotaz);
    if(!$this->parse){
      $this->Error=(@odbc_error($this->conn)).'; '.(@odbc_errormsg($this->conn)).'; SQL:'.$dotaz; 
      $this->stav=true;
      return $this->stav;
    }
    $this->p_sloupcu=@odbc_num_fields($this->parse); 
    if(!$this->parse){ 
      $this->stav=true;
      $this->Error='Parse error';
      return $this->stav;      
    }else{
      $this->stav=false;
      $this->Error='';
      return $this->stav;
    }
  }  
 
  /** $result = $db->FetchRow();
   * 
   * Provide fetch of one row of the data from the database table to the local Hash
   * @return mixed  true when next row has been fetched, false at the end of data
   */ 
  function FetchRow(){
    if($this->data=@odbc_fetch_array($this->parse)){
      /*if($vysledek=odbc_fetch_into($this->parse,$this->data))*/
      foreach ($this->data as $klic=>$pol ){
        $this->data[$klic]=iconv("windows-1250","utf-8",$pol);
      }
      return $this->data;
    }else{
      return false;
    }   
  }
  
  /** $error = $db->Pragma("table_info('TABLE_NAME'");
   * 
   * Provide special non- standartized task with the database - supported is table_info pragma 
   * @param string $command - table info pragma
   * @return boolean, true when an error has occured, false on success
   */
  function Pragma($dotaz){
    /* metoda vraci strukturu s udaji - napr. struktura tabulky a nebo false v pripade chyby*/
    /* duvodem teto metody je sjednoceni pristupu k datovemu katalogu napric databazemi */
    $m=array(); $struktura=array();
    if (preg_match('/^\s*table_info\(\'(\w+)\'\)\s*$/',$dotaz,$m)){
      $table_name=$m[1];       
      /* vrat strukturu tabulky $m[1]*/
      $cols = odbc_exec($this->conn,'select * from `'.$table_name.'` where 1=2');  /* we don't want content */
      if (!$cols) return false;
      $ncol = odbc_num_fields($cols);
      for ($n=1; $n<=$ncol; $n++) {
        $field_name = odbc_field_name($cols, $n);  /* cislovani poli je od jedne */
        if ($n==1){$pk=1;}else{$pk=0;}
        $type=odbc_field_type($cols, $n);
        $struktura[$n-1]=array(
           'name'=>$field_name,
           'type'=>$type,  
           'len'=>odbc_field_len($cols, $n),
           'presision'=>odbc_field_precision($cols, $n),
           'scale'=>odbc_field_scale($cols, $n),
           'pk'=>$pk,
           'datename'=>($type=='DATETIME'?"format($field_name,'DD.MM.YYYY') as _$field_name":$field_name)
        );         
      }
      /* odbc_primarykeys($this->conn,'Engine','',$table_name); 
          odbc_specialcolumns($this->conn,1,1,1,$table_name,1,1); nefunguje */
      //htpr(print_r($struktura,true));   
      return $struktura; 
    }
    if (preg_match('/^\s*catalog\s*$/',$dotaz,$m)){
      /* vraci seznam tabulek - pohledu */
      //if (!($this->parse=$this->conn->query("select * from sqlite_master order by name asc"))) return false;
      if (!($tablelist = odbc_tables($this->conn))) return false;
      $n=0;
      while (odbc_fetch_row($tablelist)) {
        if (odbc_result($tablelist,4) == "TABLE"){
          $struktura[$n++]=array(
           'name'=>odbc_result($tablelist,3),
           'type'=>'table'           
          );
        }
      }   
      return($struktura);
    }     
    $this->stav=false;
    return $this->stav;
  }
  
  /** $result = $db->FetchRowA();
   * 
   * Provide fetch of one row of the data from the database table to the local Array
   * @return boolean, true when next row has been fetched, false at the end of data
   */
  function FetchRowA(){
    if(@odbc_fetch_into($this->parse,$this->data)){
      foreach ($this->data as $klic=>$pol ){
        $this->data[$klic]=iconv("windows-1250","utf-8",$pol);
      }
      return $this->data;
    }else{
      return false;
    }   
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
   * @return hash with the current fetched row values
   */
  function DataHash(){
    return $this->data;
  }  
 
  /** $db->Close();
   * 
   * Closes the database connection
   */ 
  function Close(){
    if ($this->conn) {
      @odbc_close($this->conn);
    }
  }
  
}
 
?>