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
 *  Special data searching instecting can be extended from this class
 *  18.10.2022 24.10.2022 09.01.2023 11.01.2023 27.01.2023 - viz M5::get('DATA')
 *  09.03.2023 
 *  20.06.2023
 *  15.08.2023 - possibility to globally control the list page length
 */ 
include_once "mbt.php";

class VisTab {

var $separator='~';
var $db, $nastrane, $param, $sprikaz, $cprikaz, $pragma, $dprikaz, $header, $postlink, $pk,
 $filter;

function __construct($param,$db){

  $this->db=$db;
  $this->nastrane=isset($GLOBALS['vistab_n'])?$GLOBALS['vistab_n']:15;
  $this->param=$param;
  if (isset($param['table'])){
    $t=$param['table'];
    $this->header=$t;
    $this->sprikaz="select * from $t ";
    $this->cprikaz="select count(*) as pocet from $t";
    $this->pragma=$this->db->Pragma("table_info('$t')");
    $this->dprikaz=$this->sprikaz;
    /* primarni klic z pragma informace */
    if ($this->pragma)
     for($i=0,$pk='';$i<count($this->pragma);$i++)
      if (isset($this->pragma[$i]['pk'])) 
        $pk.=($pk==''?'':',').$this->pragma[$i]['name'];
    $this->pk=$pk;    
  }elseif(isset($param['sprikaz']) && isset($param['cprikaz'])){
    $this->header=(isset($param['header'])?$param['header']:'');
    $this->sprikaz=$param['sprikaz'];
    $this->cprikaz=$param['cprikaz'];
    $this->pragma=$param['pragma'];
    $this->dprikaz=$param['dprikaz'];
  }
  if (isset($param['postlink']) && $param['postlink']){
    $this->postlink=true;  
  }else{
    $this->postlink=false;
  } 
  $this->getfilter(); /* rozbaleni filtru podle parametru flt */

  /* generovani where probiha vzdy */
  setpar('_whr',$this->genwhere()); /* interne se parametr odkazuje pomoci getpar('_whr') */
}

function route($context){
  
  if (getpar('_se')){
    $this->form_param($context);
  }elseif (getpar('_det')){
    $this->detail($context);   
  }else{
    $this->lister($context);
  }

}

/** list table of rows
 */
function lister($context){
  /* zpracovani potvrzeneho formulare pro omezeni - filtrovani */
  if (getpar('_sg')) {     
    setpar('_whr',$this->genwhere());
    setpar('_flt',urlencode($this->packfilter()));
  }
  
  $sprikaz=$this->genfilter($this->sprikaz);
  $cprikaz=$this->genfilter($this->cprikaz);
  
  $a=$this->db->SqlFetchArray($sprikaz,[],isset($GLOBALS['vistab_n'])?$GLOBALS['vistab_n']:15,getpar('_ofs',1));
  /* generovani linku pro prechod do detailu */
  if (count($a)>0 ){
    if (!isset($this->param['noDetail'])){
      for($i=0,$j=getpar('_ofs',1);$i<count($a);$i++,$j++){
        if ($this->postlink){
          $a[$i]['detail']=postLink('?'.$context,bt_icon('file-text'),
           ['_det'=>'1','_o'=>getpar('o'),'_flt'=>getpar('_flt'),'_ofs'=>$j],'class="card text-primary"');
        }else{
          $l='?_det=1&amp;_o='.getpar('_o').'&amp;_flt='.getpar('_flt').'&amp;_ofs='.$j;
          $a[$i]['detail']=ahref($l.$context,bt_icon('file-text'));
        }        
      }
    }    
    /* modifikace nactene tabulky pred jejim zobrazenim - doplneni odkazu */
    //deb($a);
    if (isset($this->param['pragma'])){
      $t=$this->param['pragma'];
      for($i=0;$i<count($t);$i++){
        if (isset($t[$i]['paralink'])){
          $tt=$t[$i]['paralink'];
          for($j=0;$j<count($a);$j++){
            $link='?';
            foreach ($tt as $k=>$v){
              $link.=($link==''?'':'&').$k.'='.(is_array($v)?$a[$j][$v[0]]:$v);
            }
            $a[$j][$t[$i]['name']]=ahref($link,$a[$j][$t[$i]['name']]);
          }
        }  
      }
    }

    /* tisk tabulky a listovani */
    htpr(
      bt_lister(
        $this->header,
        $this->column_labels(),
        $a,
        'Nejsou záznamy.',
        '',
        bt_pagination(
          getpar('_ofs',1),
          $this->db->SqlFetch($cprikaz),
          isset($GLOBALS['vistab_n'])?$GLOBALS['vistab_n']:15,
          $context.'&_o='.getpar('_o').'&_flt='.getpar('_flt'),
          $this->postlink
        ),
        $context.'&_flt='.getpar('_flt'),
        $this->postlink,
        null,
        isset($this->param['text_button'])?$this->param['text_button']:'')
    );
  }else{
    htpr(bt_alert('Nejsou záznamy','alert-warning'));
    $this->form_param($context);
  }
}

/** parametric form for filtering the table view 
 *  to be overriden in extented class based on VisTab
*/
function form_param($context){
  //$this->dewhere(base64_decode(getpar('_whr')));
  /*$a=$this->db->Pragma("table_info('$t')");*/
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
  $b[$i]=[nbsp(1),submit('_st','Storno','btn btn-secondary'),submit('_sg','Vyhledej','btn btn-primary')];
  htpr(tg('form',
          'method="post" action="?'.$context.'&_o='.getpar('_o').'"',
           bt_container(['col-4','col-2','col-6'],$b))); 
}

/** based on pragma, it constructs the labels fo columns needed by bt_lister
 * attributes not listed in table have set attribute 'nolist' to true in pragma.
 */ 
function column_labels(){
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

/** genwhere - generovani podminky where z parametrickeho formulare 
 * 
*/
function genwhere(){
 
  $DAT=M5::get('DATA');
  //$DAT=$GLOBALS['DATA'];
  $where='';
  $find_ascii=false;
  if (getpar('GPA_')){
    /* generovani where u sestavovane podminky */
    $spojka=''; $zav=0;
    
    foreach ($DAT as $pol => $value){
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
    foreach ($DAT as $pol => $value){
      if (preg_match("/^(.+)_par$/",$pol, $match)){ /* prochazej dvojice ATTR a ATTR_par*/       
        $bezpar = $match[1];
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
          }elseif ($DAT[$pol] == 'in' or $DAT[$pol] == 'not in'){
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
  return $where;
}

/** packs filter params as one param named _flt  
 * 
*/
function packfilter(){  
  //$DAT=M5::getparm();
  //$DAT=$GLOBALS['DATA'];
  $DAT=M5::get('DATA');
  $s='';
  foreach ($DAT as $pol => $value){
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

/** packs filter params as one param named _flt  
 * @return string
*/
function packfilter0(){ 
  $s='';
  foreach ($this->filter as $k=>$v) if (preg_match("/^(.+)_par$/",$k, $m)){
    $ka=$m[1];
    $s.=($s==''?'':$this->separator).$ka.$this->separator.($this->filter[$k]).
     $this->separator.($this->filter[$ka]); 
  }
  return $s;
}

/** retrieve the flt parameter and stores it to the normal params */
function getfilter(){
  $flt=getpar('_flt');
  //deb('Rozbaleni',false);
  if ($flt!=''){
     $flt=urldecode($flt);
     //deb($flt,false);
     $F=explode($this->separator,$flt);
     //deb($F,false);
     for($i=0;$i<count($F);$i=$i+3){
       setpar($F[$i],$F[$i+2]);
       setpar($F[$i].'_par',$F[$i+1]);
       //deb($F[$i],false);
     }
  }
  //deb($GLOBALS['DATA'],false);
  //deb(M5::get('DATA'),false);
}


/** sestaveni podminky where a serazeni do zadaneho prikazu  
 * @param  string $prikaz
 * @return string 
 * 
*/
function genfilter($sprikaz){
  $where=getpar('_whr');
  $sprikaz=preg_replace("/\x0d/",' ',$sprikaz);
  $sprikaz=preg_replace("/\x0a/",' ',$sprikaz); //odstran odradkovani, aby fungoval r. vyraz
  $oby=(getpar('_o')!='')?(' '.getpar('_o')):'';
  $whr=(getpar('_whr')!='')?(' where '.$where):'';
  $whradd=(getpar('_whr')!='')?(' and '.$where):'';
  
  if ($oby!=''){
    if (preg_match("/^(select\s+.*) where (.+) order by (.+)$/i",$sprikaz,$match)){
      $sprikaz=$match[1].' where ('.$match[2].$whradd.') order by '.$oby; 
    }elseif (preg_match("/^(select\s+.*) order by (.+)$/i",$sprikaz,$match)){
      $sprikaz=$match[1].$whr.' order by '.$oby;
    }elseif (preg_match("/^(select\s+.*) where (.+)$/i",$sprikaz,$match)){
      $sprikaz=$match[1].' where ('.$match[2].$whradd.') order by '.$oby;
    }else{
      $sprikaz.=$whr.' order by '.$oby;
    }
  }else{
    if (preg_match("/^(select\s+.*) where (.+) order by (.+)$/i",$sprikaz,$match)){
      $sprikaz=$match[1].' where ('.$match[2].$whradd.') order by '.$match[3]; 
    }elseif (preg_match("/^(select\s+.*) order by (.+)$/i",$sprikaz,$match)){
      $sprikaz=$match[1].$whr.' order by '.$match[2];
    }elseif (preg_match("/^(select\s+.*) where (.+)$/i",$sprikaz,$match)){
      $sprikaz=$match[1].' where ('.$match[2].') '.$whradd;
    }else{
      $sprikaz.=' '.$whr;
    }
  }
  //deb($sprikaz);
  return $sprikaz;  
}

/** dewhere - pro formular parametru provede zpetne prevedeni where na parametry
 *  pokud je where ve tvaru konjukce podminek AND, prevod se povede
 *  @param $where
 *  result - set of M5 script parametres
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
}

/** This method is to be overvrited with "inteligent" from attribute filter to text conversion 
 * @return string Textual form of filter
*/
function text_filter(){
  return urldecode(getpar('_flt'));
}

/** Stranka s detailem zaznamu
 * 
 */
function detail($context,$custom=''){

  /* pritahnuti vety dprikaz - sestaveni podminky na zaklade znalosti pk */
  
  $cprikaz=$this->genfilter($this->cprikaz);
  $dprikaz=$this->genfilter($this->dprikaz);
  if ($custom==''){
    $r=$this->db->SqlFetchArray($dprikaz,[],1,getpar('_ofs',1));
    /* popisy polozek mohou byt z popisu entity v databazi */
    $p=[];
    for($i=0;$i<count($this->pragma);$i++)
      if (isset($this->pragma[$i]['comment']) && $this->pragma[$i]['comment']!='')
        $p[$this->pragma[$i]['name']]=$this->pragma[$i]['comment'];
      else 
        $p[$this->pragma[$i]['name']]=$this->pragma[$i]['name'];

    $b=[[]];$i=0;
    foreach ($r[0] as $k=>$v){
      if (isset($p[$k])){
        $b[$i][0]=ta('b',$p[$k]);
        $b[$i][1]=$v; 
        $i++;
      }  
    }
    $custom=bt_container(['col-4','col-8'],$b);
  }
  
  /* pocet zaznamu a listovani po zaznamech */
  $cprikaz=$this->genfilter($this->cprikaz);
  $ofs= getpar('_ofs')-getpar('_ofs')%$this->nastrane+1; /* navratovy offset odkazuje na naslitovanou stranku */
  if ($this->postlink){
    $back=postLink('?'.$context,'Zpět',
                   ['_o'=>getpar('_o'),
                   '_flt'=>getpar('_flt'),
                   '_ofs'=>$ofs],
                   'class="btn btn-primary"');
  }else{
    $back=ahref('?_o='.getpar('_o').'&amp;_flt='.getpar('_flt').'&amp;_ofs='.$ofs.$context,
      'Zpět',
      'class="btn btn-primary"');
  }

  htpr((getpar('_whr')?tg('div','class="m-2"',bt_alert('Filter: '.$this->text_filter())):''),
       bt_pagination(
            getpar('_ofs',1),
            $this->db->SqlFetch($cprikaz),
            1,
            $this->postlink?($context.'&_det=1&_flt='.getpar('_flt')):($context.'&_o='.getpar('_o').'&_flt='.getpar('_flt').'&_det=1'),
            $this->postlink
          ),
       $custom,
       $back
      );


}

} /* class Editab is the VisTab listing/filtering/sorting functionality with editable detail */

class EdiTab extends VisTab{

var $mode='',$bind=[],$iprikaz,$uprikaz,$rprikaz,$rowid,$eprikaz;

function __construct($param,$db){
    parent::__construct($param,$db);
    if (getpar('_det')){
      /* detail form or detail form action */
      if (isset($param['table'])){
        $t=$param['table'];
        $this->iprikaz="insert into $t ";
        $this->uprikaz="update $t set ";
        $this->rprikaz="delete from $t where ";
        $ip1='';$ip2='';
        for ($i=0;$i<count($this->pragma);$i++){
          $name=$this->pragma[$i]['name']; 
          if ($this->pragma[$i]['type']=='DATE'){
            $pole="to_date(:".$name.",'DD.MM.YYYY HH24:MI:SS') ";
          }else{
            $pole=":".$name;
          }  
          //$this->uprikaz.=($i==0?'':',').$this->column[$i].'='."'#".$this->column[$i]."#'";
          $this->uprikaz.=($i==0?'':', ').$name.'='.$pole;
          /* bind array */
          $ip1.=($i==0?'':', ').$name;
          $ip2.=($i==0?'':', ').$pole;
        }
        $this->uprikaz.=" where ";
        $this->iprikaz.='('.$ip1.') values ('.$ip2.')'; 
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
            if (isset($this->pragma[$i]['pk'])){
              $name=$this->pragma[$i]['name']; 
              $rc.=($rc==''?'':' and ').($name.'='.':'.strtolower($name));
              $this->bind[':'.strtolower($name)]=getpar(strtolower($name));
            }
          $this->uprikaz.=$rc;   
          $this->rprikaz.=$rc;
        }  
    }else{
      if (getpar('_upd')){
        $this->uprikaz=isset($param['uprikaz'])?$param['uprikaz'][0]:'';
        $this->bind=$param['uprikaz'][1];
      }elseif (getpar('_del')){
        $this->rprikaz=isset($param['rprikaz'])?$param['rprikaz'][0]:'';
        $this->bind=$param['rprikaz'][1];
      }elseif (getpar('_ins')){
        $this->iprikaz=isset($param['iprikaz'])?$param['iprikaz'][0]:'';
        $this->bind=$param['iprikaz'][1];
      }
    }
  }  
}

function detail_form($context,$data=null){
    return tg('form','method="post" action="?'.$context.'"',
     para('_o',getpar('_o')).para('_flt',getpar('_flt')).para('_ofs',getpar('_ofs')).
     '[replace]');
}
  
function detail($context,$custom=''){
    $this->eprikaz=$this->genfilter($this->dprikaz);
    $db=$this->db;
    if ($this->mode=='I'){
      $data=[]; 
      foreach($this->pragma as $k=>$v){
        $data[$v['name']]=''; /* empty form fields */
      }
    }elseif ($this->mode=='i'){
      /* navrat z neuspesneho pokusu o ulozeni - zopakuj POST polozky do editacnich poli */
      $data=M5::getparm();
      $this->mode='I'; /* dalsi pokus o ulozeni nove vety */
    }else{
      $r=$db->SqlFetchArray($this->eprikaz,[],1,getpar('_ofs',1));
      $data=$r[0];
      if (isset($this->param['rowidcolumn'])){
        $this->rowid=$data[$this->param['rowidcolumn']];
      }
    }       
    //parent::detail($context,$this->detail_form($data,$context));
    $original_primary='';
    if ($custom==''){
      $r=$this->db->SqlFetchArray($this->eprikaz,[],1,getpar('_ofs',1));
      /* popisy polozek mohou byt z popisu entity v databazi */
      $p=[];
      for($i=0;$i<count($this->pragma);$i++){
        if (isset($this->pragma[$i]['comment']) && $this->pragma[$i]['comment']!='')
          $p[$this->pragma[$i]['name']]=$this->pragma[$i]['comment'];
        else 
          $p[$this->pragma[$i]['name']]=$this->pragma[$i]['name'];
        if (isset($this->pragma[$i]['pk'])) 
          $original_primary.=para(strtolower($this->pragma[$i]['name']),$r[0][$this->pragma[$i]['name']]); 
      }
      $b=[[]];$i=0;
      foreach ($data as $k=>$v){
        if (isset($p[$k])){
          $b[$i][0]=ta('b',$p[$k]);
          $b[$i][1]=textfield('',$k,40,40,$v); 
          $i++;
        }  
      }
      $b[$i]=[nbsp(1),
              gl( ($this->mode=='I')?
                   gl(submit('_ins','Vložit','btn btn-primary')):
                   gl(submit('_upd','Uložit','btn btn-primary'),nbsp(5),
                     submit('_del','Smazat','btn btn-secondary'),
                     $original_primary), 
                 para('_o',getpar('_o')),
                 para('_flt',getpar('_flt')),
                 para('_ofs',getpar('_ofs')),
                 para('_det',1)) 
             ];
      $custom=tg('form','method="post" action="?'.$context.'"',
        ta('fieldset',
         bt_container(['col-4','col-8'],$b)));
   }
    
    /* pocet zaznamu a listovani po zaznamech */
    if ($this->mode=='I') setpar('_ofs',1); /* pri vkladani noveho zaznamu se listovani da na zacatek */
    $cprikaz=$this->genfilter($this->cprikaz);
    $ofs= getpar('_ofs')-getpar('_ofs')%$this->nastrane+1; /* navratovy offset odkazuje na naslitovanou stranku */
    if ($this->postlink){
      $back=postLink('?'.$context,'Zpět',
                     ['_o'=>getpar('_o'),
                     '_flt'=>getpar('_flt'),
                     '_ofs'=>$ofs],
                     'class="btn btn-primary"');
    }else{
      $back=ahref('?_o='.getpar('_o').'&amp;_flt='.getpar('_flt').'&amp;_ofs='.$ofs.$context,
        'Zpět',
        'class="btn btn-secondary"');
    }     
  
    htpr(
      (getpar('_whr')?bt_alert('Filter: '.$this->text_filter()):''),
       bt_pagination(
        getpar('_ofs',1),
        $this->db->SqlFetch($cprikaz),
        1,
        $this->postlink?($context.'&_det=1&_flt='.getpar('_flt')):($context.'&_o='.getpar('_o').'&_flt='.getpar('_flt').'&_det=1'),
        $this->postlink
       ),
       $custom,
       $back);
    
}
  
function route($context){  
    if (getpar('_se')){
      $this->form_param($context);
    }elseif (getpar('_det')){
      $result=false;      
      if (getpar('_upd')){
        $this->update();
      }elseif (getpar('_del')){
        $result=$this->delete();
      }elseif (getpar('_ins')){
        $result=$this->insert();
      }elseif (getpar('_blank')){
        $this->mode='I';
      }
      if ($result){
        $this->lister($context);
      }else{
        $this->detail($context);
      }
    }else{
      $this->lister($context);
    }
  }
  
  /** insert action
   * @return bool $result means to stay in detail - in case of update always
   */
  
   function insert(){
    //deb($this->iprikaz,false);deb($this->bind,false);
    $er=$this->db->Sql($this->iprikaz,$this->bind);
    if (!$er){
      htpr(bt_alert('Záznam vložen'));
      setpar('_ofs',1);
      return true;
    }else{
      htpr(bt_alert('Záznam nebyl uložen '.$this->db->Error,'alert-danger'));
      $this->mode='i';
      return false;
    }     
}
  
  /** update action
   * @return bool $result means to stay in detail - in case of update always
   */
function update(){
    $er=$this->db->Sql($this->uprikaz,$this->bind);
    if (!$er){
      htpr(bt_alert('Záznam byl uložen'));
    }else{
      htpr(bt_alert('Záznam nebyl uložen '.$this->db->Error,'alert-danger'));
    }
    return true;  
}
  
  /** delete action
   * @return bool $result means to stay in detail - in case of update always
   */
function delete(){
    //deb($this->rprikaz,false);deb($this->bind,false);
    $er=$this->db->Sql($this->rprikaz,$this->bind);
    if (!$er){
      htpr(bt_alert('Záznam smazán'));
      setpar('_ofs',1);
      return true;
    }else{
      htpr(bt_alert('Záznam nebyl smazán '.$this->db->Error,'alert-danger'));
      return false;
    }  
}
  
  /** lister
   * @param string $context
   */
function lister($context){
    parent::lister($context);
    htpr(ahref('?'.$context.'&_blank=1&_det=1','Nový záznam','class="btn btn-primary"'));    
}
  
}
   
?>