<?php
/** Data visualization and access to the view or edit detail
 * @author Petr Coupek
 * 
 *  class for table view, filtering sorting and more.
 *  call example:
 *    $db= new OpenDB_Oracle($napojeni);
 *    $tt= new VisTab(['table'=>$t],$db); 
 *    $tt->route("&vyhl=1&ROL_=1");
 *  or:
 *    $tt= new VisTab(['sprikaz'=>"select ...", 
 *                     'cprikaz'=>'select ..',
 *                     'pragma'=>'..',
 *                     'dprikaz'=>'..'],$db); 
 *  or: editing
 *   $tt= new EdiTab(['table'=>$t],$db);
 *   $tt->route("&vyhl=1&ROL_=1"); 
 * 
 *  18.10.2022 24.10.2022 09.01.2023 11.01.2023 27.01.2023 - viz M5::get('DATA')
 *  09.03.2023 
 *  20.06.2023
 *  15.08.2023 - possibility to globally control the list page length
 *  08.01.2024 - genfilter - remove order by in count select by default 
 *  21.06.2024 - parametric form should contain a submit button named as _sg 
 *  12.09.2024 - podpora metody zobrazeni filtru, metoda filter_to_array
 *  30.09.2024 - vychozi obsluha datumoveho typu podle cilove databaze
 *  03.10.2024 - opravy v __contruct
 *  08.11.2024 - moznost entita tecka atribut v podmince - $_POST tecky rusi - viz genwhere je nahrazeno ____
 *  13.11.2024 - oprava storno podminky v parametrickem formulari (pri odeslani formulare pomoci Enter se nenastavil spravne filtr)
 *  29.11.2024 - oprava uzivatelskeho razeni - kolikze s chovanim select-offset-fetch - musi byt jasne pora
 *  03.12.2024 - index pokračuje detailem
 *  06.12.2024 - pevné omezení fixed_where na entitu, kterou se prochází, oprava indikace filtr
 *  18.12.2024 - prejmenovani promennych
 *  12.02.2025 - oprava HTML injection
 *  18.03.2025 - úprava filter, vracení, detail_single je pro nahrazení.
 *  20.03.2025 - rownum v konstruktoru, pokud není pk
 *  */ 
include_once "mbt.php";

class VisTab {

var $separator='~',    /* char(s) used as separator for where condition among PUT/GET reuests */
    $db,               /* connected database objects - OpenDB_* class */
    $rowsPerPage,      /* no of rows on lister page */
    $param,            /* hash of parameters for setting the class */
    $sCmd,          /* intrinsic select command for list of records */
    $cCmd,          /* intrinsic count command */
    $pragma,           /* content of meta information about a database attributes (types,lengths,labels) */
    $dCmd,          /* intrinsic select command for detail page, should be same as sCmd with more attributes in detail */ 
    $header,           /* table header for listing */
    $postlink,         /* POST method used in listing */
    $pk,               /* primary key of the entity - an attribute or list of attributes, used also when custom sort */
    $filter,           /* generater filter based on .. */
    $classdetail,      /* detail page css class */
    $debug_mode=false, /* debug mode */
    $backText='&lt;',  /* text for back button */
    $where='';         /* temporary where */

 /** @param array $param - array of parametres
  *  @param object $db - opened DB connextion
  */
function __construct($param,$db){

  $this->db=$db;
  $this->rowsPerPage=isset($GLOBALS['vistab_n'])?$GLOBALS['vistab_n']:15;
  $this->param=$param;
  
  if (isset($param['debug_mode']) && $param['debug_mode']){
    $this->debug_mode=true;
  }
  
  /* the display object is either a table or a select command */
  if (isset($param['table'])){
    $t=$param['table'];
    $this->header=$t;
    $this->sCmd="select * from $t ";
    $this->cCmd="select count(*) as pocet from $t";
    $this->pragma=$this->db->Pragma("table_info('$t')");
    $this->dCmd=$this->sCmd;
    /* primary key taken from pragma */
    if ($this->pragma)
     for($i=0,$pk='';$i<count($this->pragma);$i++)
      if (isset($this->pragma[$i]['pk']) && $this->pragma[$i]['pk']) 
        $pk.=($pk==''?'':',').$this->pragma[$i]['name'];
    $this->pk=$pk;    
  }elseif(isset($param['sprikaz']) && isset($param['cprikaz'])){ /* backword compatibility */
    $this->header=(isset($param['header'])?$param['header']:'');
    $this->sCmd=$param['sprikaz'];
    $this->cCmd=$param['cprikaz'];
    $this->pragma=$param['pragma']; 
    $this->dCmd=(isset($param['dprikaz'])?$param['dprikaz']:$this->sCmd);
    $this->pk=(isset($param['pk'])?$param['pk']:'rownum');
  }elseif(isset($param['sCmd']) && isset($param['cCmd'])){
    $this->header=(isset($param['header'])?$param['header']:'');
    $this->sCmd=$param['sCmd'];
    $this->cCmd=$param['cCmd'];
    $this->pragma=$param['pragma']; 
    $this->dCmd=(isset($param['dCmd'])?$param['dCmd']:$this->sCmd);
    $this->pk=(isset($param['pk'])?$param['pk']:'rownum');
  }else{
    $this->pk=(isset($param['pk'])?$param['pk']:'rownum');
  }
  if (isset($param['postlink']) && $param['postlink']){
    $this->postlink=true;  
  }else{
    $this->postlink=false;
  }
  if (isset($param['classdetail'])){
    $this->classdetail=$param;  
  }else{
    $this->classdetail='bg-light border p-2';
  }

  /* filter expansion according to the flt parameter, if new conditions are not searched for */
  $this->getfilter(); 

  /* the generation of "where condition" is always based on the parameter wrapped in _flt*/
  
  $this->where=$this->genwhere();
  if ($this->debug_mode){
    deb('VISTAB construct, _whr: '.$this->where,false);
  }
  $this->backText=isset($param['backText'])?$param['backText']:(bt_icon('chevron-left').'Zpět');	
}

/** the route method - starting point for the component 
 * @param string $context The path to the file.
 */

function route($context){
  if (getpar('_se')){
    htpr($this->form_param($context));
  }elseif (getpar('_det')){
    htpr($this->detail($context));
  }else{
    htpr($this->lister($context));
  }
}

/** 
 * the method for visualizing the entity data
 * @param string $context - the page parameters contex
*/
function lister($context){
  /* zpracovani potvrzeneho parametrickeho formulare - zde je nutne vygenerovat _flt pro omezeni - filtrovani */
  $r='';
  if (getpar('_sg')) {     
    setpar('_flt',urlencode($this->packfilter()));
  }  
  $sCmd=$this->genfilter($this->sCmd);
  $cCmd=$this->genfilter($this->cCmd,false);
    
  $a=$this->db->SqlFetchArray($sCmd,[],isset($GLOBALS['vistab_n'])?$GLOBALS['vistab_n']:15,getpar('_ofs',1));
  /* generovani linku pro prechod do detailu */
  if (count($a)>0 ){
    if (!isset($this->param['noDetail'])){
      for($i=0,$j=getpar('_ofs',1);$i<count($a);$i++,$j++){
        if ($this->postlink){
          $a[$i]['detail']=postLink('?'.$context,bt_icon('menu'),
           ['_det'=>'1','_o'=>getpar('o'),'_flt'=>getpar('_flt'),'_ofs'=>$j],'class="card text-primary"');
        }else{
          $l='?_det=1&amp;_o='.getpar('_o').'&amp;_flt='.getpar('_flt').'&amp;_ofs='.$j;
          $a[$i]['detail']=ahref($l.$context,bt_icon('menu'), 'id="detail-icon"');
        }        
      }
    }
    
    /* modifikace nactene tabulky pred jejim zobrazenim - doplneni odkazu kamkoliv */
    if (isset($this->pragma)){
      $t=$this->pragma;
      for($i=0;$i<count($t);$i++){
        /* zpracuj, jen pokud je to definovano */
        if (isset($t[$i]['replace']) && isset($t[$i]['replace']['temp']) && isset($t[$i]['replace']['pars'])){
          $pars=$t[$i]['replace']['pars'];
          for($j=0,$o=getpar('_ofs',1);$j<count($a);$j++,$o++){
            $temp=$t[$i]['replace']['temp'];
            for($k=0;$k<count($t[$i]['replace']['pars']);$k++){
              $temp=str_replace($pars[$k],$a[$j][$pars[$k]],$temp);
              $temp=str_replace('_OFS_',$o,$temp);
              $temp=str_replace('_FLT_',getpar('_flt'),$temp);
              $temp=str_replace('_O_',getpar('_o'),$temp);
            }
            /* osetreni vraceni prazdneho pole, pokud je i datovy atribut nevyplaneny - napr. chybi nahled snimku */
            if (isset($t[$i]['replace']['temp_cond']) && $t[$i]['replace']['temp_cond'] && $a[$j][$t[$i]['name']]==''){
              continue; // toto neni potreba: $a[$j][$t[$i]['name']]='';   
            }else{
              $a[$j][$t[$i]['name']]=$temp;         
            }  
          }  
        }  
      }
    }

    /* modifikace volanim metody modify_row */
    for($i=0;$i<count($a);$i++){
      $a[$i]=$this->modify_row_before_print($a[$i], $context);
    }

    /* tisk tabulky a listovani, $a je obsah/tabulka */
    $r.=gl(
      bt_lister(
        $this->header,
        $this->column_labels(),
        $a,
        (isset($this->param['no_data'])?$this->param['no_data']:'Nejsou záznamy.'),
        '',
        bt_pagination(
          getpar('_ofs',1),
          $this->db->SqlFetch($cCmd),
          isset($GLOBALS['vistab_n'])?$GLOBALS['vistab_n']:15,
          $context.'&_o='.getpar('_o').'&_flt='.getpar('_flt'),
          $this->postlink
        ),
        $context.'&_flt='.getpar('_flt'),
        $this->postlink,
        $this->text_filter()!=''?('Filtrováno: '.$this->text_filter()):'',
        isset($this->param['text_button'])?$this->param['text_button']:'',
        isset($this->param['toolbar_buttons'])?$this->param['toolbar_buttons']:''));
  }else{
    $r.=gl(bt_alert(isset($this->param['no_data'])?$this->param['no_data']:'Nejsou záznamy.','alert-warning'));
    $r.=$this->form_param($context);
  }
  return $r;
}

/** to be overriden
 * @param array $row - input roww
 * @return array $row - modified row
 */
function modify_row_before_print($row, $context){
  return $row;
}

/** parametric form for filtering the table view 
 *  to be overriden in extented class based on VisTab
 * @param array $context - all the nesessary parametres for contrucct the whole page
 * @return string - a HTML content of the query (parametric) form
*/
function form_param($context){
  
  $a=$this->pragma;
  if (!is_array($a)) {
    deb('wrong pragma'); 
    return 0;
  }
  $b=array();
  for ($i=0;$i<count($a);$i++){
    if (isset($a[$i]['name'])){
      $b[$i][0]=(isset($a[$i]['comment']))?$a[$i]['comment']:$a[$i]['name'];
      $b[$i][1]=combo('',$a[$i]['name'].'_par',[
                    'like'=>'obsahuje',
                    'begins'=>'začíná',
                    '='=>'='],
                    getpar($a[$i]['name'].'_par')?getpar($a[$i]['name'].'_par'):'like');
      $b[$i][2]=textfield('',$a[$i]['name'],20,40,getpar($a[$i]['name']));
    }                  
  }
  $b[$i]=[nbsp(1),ahref('?'.$context.'&_st=1','Storno podmínky','class="btn btn-secondary m-1"'),
   submit('_sg','Vyhledej','btn btn-primary m-1')];
  return tg('form',
          'method="post" action="?'.$context.'&_o='.getpar('_o').'" class="m-2" ',
           bt_container(['col-4','col-3','col-5'],$b)); 
}

/** based on pragma, it constructs the labels for columns needed by bt_lister
 * attributes not listed in table have set attribute 'nolist' to true in pragma.
 */ 
private function column_labels(){
  $a=$this->pragma;
  if (!is_array($a)) {
    return []; // ['detail'=>'Detail'];
  }
  $b=array();
  for ($i=0;$i<count($a);$i++){
    if (isset($a[$i]['name']) && !isset($a[$i]['nolist'])){
      $b[$a[$i]['name']]=(isset($a[$i]['comment']))?$a[$i]['comment']:$a[$i]['name'];
    }                  
  }
  /* normally, add the column with link to the detail with special name */
  if (!isset($this->param['noDetail'])) $b['detail']='Detail';
  return $b;
}

/** genwhere - generate the SQL querystring form the parametric form
 * @return string - the SQL WHERE part
*/
function genwhere(){
  $DAT=M5::get('DATA');
  $where='';
  $find_ascii=false;
  if (getpar('GPA_')){
    /* generovani where u sestavovane podminky */
    $spojka=''; $zav=0;
    
    //foreach ($DAT as $pol => $value){
    foreach (array_keys($DAT) as $pol){
      if (preg_match("/^par_(\d+)$/", $pol, $match)){
        if ($DAT[$pol]!=''){
          if (strpos($DAT[$pol.'_g'],'(')!==false) {$zav++;}
          if (strpos($DAT[$pol.'_g'],')')!==false) {$zav--;}
          if ($where != ''){
            $where.=$spojka;
          }
          if ($DAT[$pol.'_p'] == 'like') {
            $DAT[$pol.'_t'] = '%'.$DAT[$pol.'_t'].'%';
          }
          if ($DAT[$pol.'_p'] == 'begins') {
            $DAT[$pol.'_t'] = $DAT[$pol.'_t'].'%';
            $DAT[$pol.'_p'] = 'like';
          }
          if ($DAT[$pol.'_p'] == 'ends') {
            $DAT[$pol.'_t'] = '%'.$DAT[$pol.'_t'];
            $DAT[$pol.'_p'] = 'like';
          }
          if ($find_ascii){
            /* prevzeto 27.04.2022 modifikoval 28.8.2014 Vaclav Pospisil - podminka bere to, ze se odbourava diakritik*/
					  $where.="upper(convert(".$DAT[$pol].",'US7ASCII')) ".$DAT[$pol.'_p']." upper(convert('".$DAT[$pol.'_t']."','US7ASCII')) ";
					}else{  
            $where.=$DAT[$pol].' '.$DAT[$pol.'_p']." '".$DAT[$pol.'_t']."' ";
          }
          $spojka=$DAT[$pol.'_g']." ";
        }
      }
    }
    /* posledni spojka by mela byt bud uzaviraci zavorka nebo je ignorovana*/
    if(($spojka==') ')||($zav>0)){
       $where.=str_repeat(')',$zav);
    }
  }else{
    /* klasicky parametricky formular */
    foreach (array_keys($DAT) as $pol){

        if (preg_match("/^(.+)_par$/",$pol, $match)){ /* prochazej dvojice ATTR a ATTR_par*/

            $bezpar = $match[1];
            if (isset($this->param['ignore_pars']) && in_array($bezpar,$this->param['ignore_pars'])) continue;
            $atribut=$bezpar;     /* $atribut obsahuje jmeno atributu, ktery je dotazovan */
            if (preg_match("/^(.+)_and(\d*)$/",$bezpar, $match)){
                $atribut= $match[1];
            }
            /* jednotlive podminky se spojuji pomoci and , ale u prvniho and neni */
        $p=($where != '')?' and ':'';
        /* null a not null nemusi mit vyplnenou hodnotu $DATA{$bezpar} muze byt prazdne */
        if ($DAT[$pol] == 'null' || $DAT[$pol] == 'not null'){
          $where.=$p."$bezpar is $DAT[$pol]";  continue;
        }
        if (isset($DAT[$bezpar]) && $DAT[$bezpar]!=''){
          $citlivost=isset($DAT[$bezpar.'_uns']) && ($DAT[$bezpar.'_uns']!='');
          if ($DAT[$pol] == 'like'){
            $DAT[$bezpar] = "'%".$DAT[$bezpar]."%'";
          }elseif ($DAT[$pol] == 'begins') {
            $DAT[$bezpar] = "'".$DAT[$bezpar]."%'";
            $DAT[$pol]='like';
          }elseif ($DAT[$pol] == 'ends') {
            $DAT[$bezpar] = "'%".$DAT[$bezpar]."'";
            $DAT[$pol]='like';  
          }elseif ($DAT[$pol] == 'in' || $DAT[$pol] == 'not in'){
            /* muze jit bud o multiselect a nebo seznam hodnot oddelenych carkou */
            if (is_array($DAT[$bezpar])){
              $p1=$DAT[$bezpar];
            }else{
              $p1=explode(',',$DAT[$bezpar]); $p2='';
            }
            foreach ($p1 as $v){
              $p2.=(($p2=='')?'':',')."'".$v."'";
            }
            $DAT[$bezpar]="( ".$p2." )";
          }else{
            $DAT[$bezpar]="'".$DAT[$bezpar]."'";
          }
          $atribut=str_replace('____','.',$atribut); /* moznost entita tecka atribut v podmince - $_POST tecky rusi*/
          if ($citlivost){
            /* podle Vaclav Pospisil - podminka bere to, ze se odbourava diakritika */
					  $where.=$p."upper(convert($atribut,'US7ASCII')) $DAT[$pol] upper(convert($DAT[$bezpar],'US7ASCII'))";
          }else{
            $where.=$p."$atribut $DAT[$pol] $DAT[$bezpar]";
          }
        }
      }      
    }
  }
  if (isset($this->param['fixed_where']) && $this->param['fixed_where']!=''){
    $where.=($where!=''?' and ':'').$this->param['fixed_where'];
  }
  return htmlspecialchars_decode($where);
}

/** packs filter params as one param named _flt  
 * 
*/
function packfilter(){  
  $DAT=M5::get('DATA');
  $s='';
  //foreach ($DAT as $pol => $value){
  foreach (array_keys($DAT) as $pol){   
    if (preg_match("/^(.+)_par$/",$pol, $m)){
      $a= $m[1];
      if (isset($DAT[$a]) && $DAT[$a]!=''){
        $s.=($s==''?'':$this->separator).$a.$this->separator.$DAT[$a.'_par'].
         $this->separator.$DAT[$a];
      }  
    }
  }
  return $s;
}

/** retrieve the flt parameter and stores it to the normal params using M5::setpar function
 * This is called only in the Vistab constructor
*/
function getfilter(){
  $flt=htmlspecialchars_decode(getpar('_flt'));
  if ($flt!=''){
     $flt=urldecode($flt);
     $F=explode($this->separator,$flt);
     for($i=0;$i<count($F);$i=$i+3){
       if (isset($F[$i]) && isset($F[$i+2])){
         setpar($F[$i],$F[$i+2]);
         setpar($F[$i].'_par',$F[$i+1]);
       }
     }
  }
}

/** generate WHERE condition and complete the final select command for the required data  
 * @param  string $prikaz
 * @return string - the SQL select command
 * 
*/
function genfilter($sCmd,$order_by=true){
  $where=$this->where;
  $sCmd=preg_replace("/\x0d/",' ',$sCmd);
  $sCmd=preg_replace("/\x0a/",' ',$sCmd); /* remove newlines to be regular expression functional */
  $oby=(getpar('_o')!='' && $order_by)?(getpar('_o')):'';
  $whr=($where!='')?(' where '.$where):'';
  $whradd=($where!='')?(' and '.$where):'';
  
  if ($oby!=''){
    if (preg_match("/^(select\s+.*) where (.+) order by (.+)$/i",$sCmd,$match)){
      $sCmd=$match[1].' where ('.$match[2].$whradd.') order by '.$oby; 
    }elseif (preg_match("/^(select\s+.*) order by (.+)$/i",$sCmd,$match)){
      $sCmd=$match[1].$whr.' order by '.$oby;
    }elseif (preg_match("/^(select\s+.*) where (.+)$/i",$sCmd,$match)){
      $sCmd=$match[1].' where ('.$match[2].$whradd.') order by '.$oby;
    }else{
      $sCmd.=$whr.' order by '.$oby;
    }
  }else{
    if (preg_match("/^(select\s+.*) where (.+) order by (.+)$/i",$sCmd,$match)){
      $sCmd=$match[1].' where ('.$match[2].$whradd.') order by '.$match[3]; 
    }elseif (preg_match("/^(select\s+.*) order by (.+)$/i",$sCmd,$match)){
      $sCmd=$match[1].$whr.' order by '.$match[2];
    }elseif (preg_match("/^(select\s+.*) where (.+)$/i",$sCmd,$match)){
      $sCmd=$match[1].' where ('.$match[2].') '.$whradd;
    }else{
      $sCmd.=' '.$whr;
    }
  }

  if ($order_by && $this->debug_mode) {
    deb('VISTAB genfilter: '.$sCmd,false);
    deb('VISTAB _o: '.getpar('_o'),false);
  }

  return $sCmd;  
}

/** dewhere - convert where condition back to the attribute parametres values
 *  pokud je where ve tvaru konjukce podminek AND, prevod se povede
 *  @param $where
 *  result - set of M5 script parametres
 *  @return bool true
 */
function dewhere($where){
  if ($where!=''){
    $a=explode(' and ',$where);
    //deb($a, false); 
    for ($i=0;$i<count($a);$i++){
      $b=explode(' ',$a[$i]);
      //deb($b[1],false);
      if (preg_match("/^'(.*)'$/",$b[2],$m)){
         $b[2]=$m[1];
      }
      if (preg_match("/^%(.*)%$/",$b[2],$m)){
        $b[2]=$m[1]; $b[1]='like';
      }
      if (preg_match("/^(.*)%$/",$b[2],$m)){
        $b[2]=$m[1]; $b[1]='begins';
      }
      setpar($b[0],$b[2]);
      setpar($b[0].'_par',trim($b[1]));
    } 
  }
  return true; 
}

/** This method is intended to be overrided with "inteligent" from attribute filter to text conversion 
 * @return string - textual form of filter
*/
function text_filter(){
  return urldecode(getpar('_flt'));
}

/**
 * @return array - it returns an array - key is the parameter name and content is relation operator + value
 */
function filter_to_array(){
  $flt=getpar('_flt');
  $par=[];
  if ($flt!=''){
    $flt=urldecode($flt);
    /* converting the filter into an array where the key is a parameter and the content is a relational operator and a value */
    $F=explode($this->separator,$flt);
    for($i=0;$i<count($F);$i=$i+3){
      if ($F[$i+1]=='*') $F[$i+1]='=';
      $par[$F[$i]]=array($F[$i+1],$F[$i+2]);    
    }
  }
  return $par;
}

/** The detail page
 * @param string $context
 * @return string - detail html content
 */
function detail($context, $custom = ''){
  
  /* pritahnuti vety dprikaz - sestaveni podminky na zaklade znalosti pk */  
  $cCmd=$this->genfilter($this->cCmd,false);
  
  $custom=$this->detail_single($context);
  
  /* pocet zaznamu a listovani po zaznamech */
  $ofs= getpar('_ofs')%$this->rowsPerPage;
  if ($ofs==0) $ofs=$this->rowsPerPage;
  $ofs= getpar('_ofs')-$ofs+1; /* navratovy offset odkazuje na naslitovanou stranku */
  if ($this->postlink){
    $back=postLink('?'.$context,$this->backText,
                   ['_o'=>getpar('_o'),
                   '_flt'=>getpar('_flt'),
                   '_ofs'=>$ofs],
                   'class="btn btn-secondary m-2"');
  }else{
    $back=ahref('?_o='.getpar('_o').'&amp;_flt='.getpar('_flt').'&amp;_ofs='.$ofs.$context,
       $this->backText,
      'class="btn btn-secondary"');
  }
  
  return 
    tg('div','id="detail" class="'.$this->classdetail.'"',
     gl(
       $this->text_filter()!=''?bt_alert('Filtrováno: '.$this->text_filter()):'',
       bt_pagination(
            getpar('_ofs',1),
            $this->db->SqlFetch($cCmd),
            1,
            $this->postlink?($context.'&_det=1&_flt='.getpar('_flt')):($context.'&_o='.getpar('_o').'&_flt='.getpar('_flt').'&_det=1'),
            $this->postlink
          ),
       $custom,
       $back
      ));
}

/** detail of the page with the single record
 * 
 */
function detail_single($context, $custom = ''){
  if ($custom!='') return $custom;
  $dCmd=$this->genfilter($this->dCmd,true);
  
  $r=$this->db->SqlFetchArray($dCmd,[],1,getpar('_ofs',1));
  /* popisy polozek mohou byt z popisu entity v databazi */
  $p=[];
  for($i=0;$i<count($this->pragma);$i++)
    if (isset($this->pragma[$i]['comment']) && $this->pragma[$i]['comment']!='')
      $p[$this->pragma[$i]['name']]=$this->pragma[$i]['comment'];
    else 
      $p[$this->pragma[$i]['name']]=$this->pragma[$i]['name'];

  $b=[[]];
  $i=0;
  foreach ($r[0] as $k=>$v){
    if (isset($p[$k])){
      $b[$i][0]=ta('b',$p[$k]);
      $b[$i][1]=$v; 
      $i++;
    }  
  }
  return bt_container(['col-4','col-8'],$b);
}

/** vraci parametry pro udrzeni kontextu tridy Vistab 
 * @param string $method - bud GET pro odkazy nebo POST pro formulare
*/
function vistab_params($method='GET'){
  if ($method=='GET') return '_ofs='.getpar('_ofs').'&_o='.getpar('_o').'&_flt='.getpar('_flt');
  if ($method=='POST') return para('_ofs',getpar('_ofs')).para('_o',getpar('_o')).para('_flt',getpar('_flt'));
}


} 

/** class Editab is the VisTab + listing/filtering/sorting functionality with editable detail 
 * 
*/
class EdiTab extends VisTab{

var $mode='', /* indikator stavu skriptu:
               I - stav noveho cisteho formulare pred zobrazenim,
               i - insert failed,
               u - update failed,
               e - update or insert OK  */
    $bind=[],
    $iCmd,
    $uCmd,
    $rCmd,
    $rowid,
    $eprikaz,
    $data;

function __construct($param,$db){
    parent::__construct($param,$db);
    if (getpar('_det')){
      /* detail form or detail form action */
      if (isset($param['table'])){
        $t=$param['table'];
        $this->iCmd="insert into $t ";
        $this->uCmd="update $t set ";
        $this->rCmd="delete from $t where ";
        $ip1='';$ip2='';
        for ($i=0;$i<count($this->pragma);$i++){
          $name=$this->pragma[$i]['name']; 
          if ($this->pragma[$i]['type']=='DATE'){
            if ($this->db->typedb=='oracle'){
              $pole="to_date(:".$name.",'DD.MM.YYYY HH24:MI:SS') ";
            }
            if ($this->db->typedb=='sqlite'){
              $pole=":".$name;
            }
          }else{
            $pole=":".$name;
          }  
          //$this->uCmd.=($i==0?'':',').$this->column[$i].'='."'#".$this->column[$i]."#'";
          $this->uCmd.=($i==0?'':', ').$name.'='.$pole;
          /* bind array */
          $ip1.=($i==0?'':', ').$name;
          $ip2.=($i==0?'':', ').$pole;
        }
        $this->uCmd.=" where ";
        $this->iCmd.='('.$ip1.') values ('.$ip2.')'; 
        $rc='';
        /* construct bind content - for insert and update */
        if (getpar('_ins') || getpar('_upd'))
          for ($i=0;$i<count($this->pragma);$i++){
            $name=$this->pragma[$i]['name'];
            $this->bind[':'.$name]=getpar($name);
        }  
        /* construct aditional bind variables - for update nad delete */
        if (getpar('_upd') || getpar('_del')){
          for ($i=0;$i<count($this->pragma);$i++)
            if (isset($this->pragma[$i]['pk']) && $this->pragma[$i]['pk']){
              $name=$this->pragma[$i]['name']; 
              $rc.=($rc==''?'':' and ').($name.'='.':'.strtolower($name));
              $this->bind[':'.strtolower($name)]=getpar(strtolower($name));
            }
          $this->uCmd.=$rc;   
          $this->rCmd.=$rc;
        }
    }else{
      if (getpar('_upd')){
        $this->uCmd=isset($param['uCmd'])?$param['uCmd'][0]:'';
        $this->bind=isset($param['uCmd'])?$param['uCmd'][1]:'';
      }elseif (getpar('_del')){
        $this->rCmd=isset($param['rCmd'])?$param['rCmd'][0]:'';
        $this->bind=isset($param['rCmd'])?$param['rCmd'][1]:'';
      }elseif (getpar('_ins')){
        $this->iCmd=isset($param['iCmd'])?$param['iCmd'][0]:'';
        $this->bind=isset($param['iCmd'])?$param['iCmd'][1]:'';
      }
    }
  }  
}

/** nevyuzito ? */
function detail_form($context,$data=null){
    return tg('form','method="post" action="?'.$context.'"',
     para('_o',getpar('_o')).para('_flt',getpar('_flt')).para('_ofs',getpar('_ofs')).
     '[replace]');
}

/** thw whole detail page 
 * includig listeing over other details 
 * and call the detail_single method
 * @param string $context - the page parameters contex 
 */
function detail($context, $custom = ''){
    $this->eprikaz=$this->genfilter($this->dCmd);
    if ($this->debug_mode) deb('EDITAB detail: '.$this->eprikaz,false);
    $db=$this->db;
    /* The empty data for blank form or POST data after unsuccesfull insert/update or take
     * the data from the database for the detail form 
     */
    if ($this->mode=='I'){
      /* new record - empty form fields */
      $this->data=[]; 
      foreach($this->pragma as $k=>$v){
        $this->data[$v['name']]=''; /* empty form fields */
      }
    }elseif ($this->mode=='i' || $this->mode=='u'){
      /* navrat z neuspesneho pokusu o ulozeni - zopakuj POST polozky do editacnich poli */
      $this->data=M5::getparm();
      $this->mode='I'; /* dalsi pokus o ulozeni nove vety */
    }else{
      /* uspesne ulozeni dat a jejich opetovne nacteni z DB - budou tam jiz napr. casove znacky z DB */
      $r=$db->SqlFetchArray($this->eprikaz,[],1,getpar('_ofs',1));
      if ($this->debug_mode) deb('EdiTab detail '.$r,false);
      $this->data=$r[0];
      if (isset($this->param['rowidcolumn'])){
        $this->rowid=$this->data[$this->param['rowidcolumn']];
      }
      setpar('_blank',''); 
    }       
       
    $custom=$this->detail_single($context); /* it uses $this->data */
    
    /* number of records and pagination, passing control parameters */
    if ($this->mode=='I') setpar('_ofs',1); /* when inserting a new record, scrolling starts at the beginning */
    $cCmd=$this->genfilter($this->cCmd,false);
    $ofs= getpar('_ofs')%$this->rowsPerPage;
    if ($ofs==0) $ofs=$this->rowsPerPage;
    $ofs= getpar('_ofs')-$ofs+1; /* the return offset refers to the page that was paged */
    
    if ($this->postlink){
      $back=postLink('?'.$context,$this->backText,
                     ['_o'=>getpar('_o'),
                     '_flt'=>getpar('_flt'),
                     '_ofs'=>$ofs],
                     'class="btn btn-secondary m-2"');
    }else{
      $back=ahref('?_o='.getpar('_o').'&amp;_flt='.getpar('_flt').'&amp;_ofs='.$ofs.$context,
         $this->backText,
        'class="btn btn-secondary m-2"');
    }     
  
    return gl(
      $this->mode!='I'?
        bt_pagination(
         getpar('_ofs',1),
         $this->db->SqlFetch($cCmd),
         1,
         $this->postlink?($context.'&_det=1&_flt='.getpar('_flt')):($context.'&_o='.getpar('_o').'&_flt='.getpar('_flt').'&_det=1'),
         $this->postlink
        ):'',/* do not draw pages for a new record */
        $custom, 
        $back
        );
}

/** Original detail form - normally to be overriden in the child class
 * @param string $context
 */
function detail_single($context, $custom=''){
  $original_primary='';
  $r=$this->db->SqlFetchArray($this->eprikaz,[],1,getpar('_ofs',1));
  /* popisy polozek mohou byt z popisu entity v databazi */
  $p=[];
  for($i=0;$i<count($this->pragma);$i++){
    if (isset($this->pragma[$i]['comment']) && $this->pragma[$i]['comment']!='')
      $p[$this->pragma[$i]['name']]=$this->pragma[$i]['comment'];
    else 
      $p[$this->pragma[$i]['name']]=$this->pragma[$i]['name'];
    if (isset($this->pragma[$i]['pk'])) 
      $original_primary.=para(strtolower($this->pragma[$i]['name']),htmlentities($r[0][$this->pragma[$i]['name']],ENT_QUOTES)); 
  }
  $b=[[]];$i=0;
  foreach ($this->data as $k=>$v){
    if (isset($p[$k])){
      $b[$i][0]=ta('b',$p[$k]);
      $b[$i][1]=textfield('',$k,40,40,$v); 
      $i++;
    }  
  }
  $b[$i]=[nbsp(1),
          gl(($this->mode=='I')?
              gl(submit('_ins','Vložit','btn btn-primary m-2')):
              gl(submit('_upd','Uložit','btn btn-primary m-2'),nbsp(5),
                 submit('_del','Smazat','btn btn-secondary m-2'),
                 $original_primary), 
              para('_o',getpar('_o')),
              para('_flt',getpar('_flt')),
              para('_ofs',getpar('_ofs')),
              para('_det',1)) 
         ];
         
  return tg('form','method="post" action="?'.$context.'"',
    ta('fieldset',
     bt_container(['col-4','col-8'],$b)));

}

/** the route method - starting point for the component 
 * @param string $context The path to the file.
 *  recomendation: for child classes: call the parent method after the custom code
 */
function route($context){  
    if (getpar('_se')){
      htpr($this->form_param($context));
    }elseif (getpar('_det')){
      $show_list=false;      
      if (getpar('_upd')){
        $this->update();
      }elseif (getpar('_del')){
        $show_list=$this->delete();
      }elseif (getpar('_ins')){
        $this->insert(); /* insert is always followed by detail */
      }elseif (getpar('_blank')){
        $this->mode='I';
      }
      if ($show_list){
        htpr($this->lister($context));
      }else{
        htpr($this->detail($context)); /* continue with detail form (also after succesufull insert) */
      }
    }elseif(getpar('_st')){
      /* it is necessary to clear the param content */
      setpar('_whr','');
      htpr(bt_alert('Podmínka nulována.'));
      htpr($this->lister($context));
    }else{
      htpr($this->lister($context));
    }
  }
  
/** insert action
 * @return bool true
 */  
function insert(){
  $er=$this->db->Sql($this->iCmd,$this->bind);
  if (!$er){
    htpr(bt_alert('Záznam vložen'));
    setpar('_ofs',1);
    $this->mode='e';
  }else{
    htpr(bt_alert('Záznam nebyl uložen '.$this->db->Error,'alert-danger'));
    $this->mode='i';
  }
  return true;     
}
  
/** update action
 * @return bool $result means 'to stay in detail' - in case of update always
 */
function update(){
  $er=$this->db->Sql($this->uCmd,$this->bind);
  if (!$er){
    htpr(bt_alert('Záznam byl uložen'));
    $this->mode='e'; 
  }else{
    htpr(bt_alert('Záznam nebyl uložen '.$this->db->Error,'alert-danger'));
    $this->mode='u';
  }
  return true;  
}
  
/** delete action
  * @return bool $result means to stay in detail - in case of update always
 */
function delete(){
  $er=$this->db->Sql($this->rCmd,$this->bind);
  if (!$er){
    htpr(bt_alert('Záznam smazán'));
    setpar('_ofs',1);
    return true;
  }else{
    htpr(bt_alert('Záznam nebyl smazán '.$this->db->Error,'alert-danger'));
    return false;
  }  
}
  
  
}
   
?>