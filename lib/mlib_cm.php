<?php
/**
 * Content Management System object
 * @author Petr Čoupek 
 * @version 1.00
 * 28.2.2020 , 23.03.2020, 30.03.2020, 31.03.2020,  6.5.2020, 14.5.2020, 15.5.2020
 * 27.5.2020, 11.6.2020 18.8.2020 - localtime SQLite
 * 17.09.2020
 * 01.11.2020 - downgrading compability on gigaserver [] => array()
 * 21.12.2020 - debugging
 * 03.02.2021 - get_groups, is_in_group, oprava log_mess dana zakazanim zavirani pripojeni ve folder
 * 08.04.2021 - ladeni bootstrap velikosti breadcrumb
 * 29.06.2021 - moznost zpracovani markdown polozek
 * 01.10.2021 - oprava formalnich chyb
 * 08.10.2021 - bind sql
 * 15.06.2022 - zakomentovani vsech prikazu debug
 * 17.10.2022 - merkur version
 * 02.12.2022 - refakturing na merkur5
 * 05.01.2023 - zotaveni z fatalnich chyb
 * 27.06.2023 - implementace uzivatelskych nastaveni
 */
define('MIC_LDAP_SERVER','ldap://10.1.8.11:389'); /* replace with correct value when used - see pattern */

register_shutdown_function( "fatal_handler" );
function fatal_handler() {
    deb(error_get_last());
    htpr('Něco se pokazilo.');
    if (error_get_last()!=NULL) {
       htpr_all(); 
       die;
    }   
}

class Cm{

  var $tree=array();  /* internal tree with menu items */
  var $db, $table, $leftside, $ace_editor, $sysdate, $begin, $end, $concat, $debug, $default_node,
   $user, $legacy, $afterEdit, $typy, $def_s_p, $dv, $def_la, $erh, $item, $err;
    
  /** constructor for the cms 
   * @param string $table - the name of the tree table
   * @param object $db - allready initialized database object
   * @param bool $leftside - indicator of the left side , default false
   * @param bool $ldap - indicator, if is used LDAP authorization or a password file
   * 
   */ 
  function __construct($table, $db, $leftside=false, $ldap=true){
     $this->table=$table;
     //$this->user=$user;
     $this->leftside=$leftside;
     $this->ace_editor=false; /* if ace JS --based editor is used for app's items */
     $this->db=$db;
     switch ($this->db->typedb){
       case 'sqlite': 
         $this->sysdate="datetime('now','localtime')";
         $this->begin="";
         $this->end="";
         $this->concat='||';
         break;
       case 'oracle':
         $this->sysdate="sysdate";
         $this->begin="begin ";
         $this->end="end;";
         $this->concat='||';
         break;
       default:
         $this->sysdate="sysdate";
     }
     $this->debug=false;
     $this->legacy=true; /* true when the folder contains "old-fashioned" scripts with using eval */
                         /* false when only include_once directive is allowed */
     $this->default_node=1;
     /* login check   */
     if (getpar('__LOG')!=''){
       $this->user=$this->check_login($ldap);
       $_SESSION['uzivatel']=$this->user;
       if ($this->user!=''){
         $this->log_mess('Přihlášen');
       }  
     }else{
       if (isset($_SESSION['uzivatel']) && $_SESSION['uzivatel']!=''){
         $this->user=$_SESSION['uzivatel'];
       }else{
          /* try system logging  */
         if (isset($_SERVER['PHP_AUTH_USER'])){
           $this->user=strtoupper($_SERVER['PHP_AUTH_USER']);
           $_SESSION['uzivatel']=$this->user;
           $this->log_mess('Automaticky přihlášen');
         }else{
           /* generating initial page - before login processing */
           $this->user='';
         }   
       }  
     }
     $this->afterEdit=false;
     
     $this->typy=array(
      'text'=>http_lan_text('text item','textová položka'),
      'url'=>http_lan_text('URL link','odkaz URL'),
      'file'=>http_lan_text('file','vložený soubor'),
      'img'=>http_lan_text('image','obrázek'),
      'app'=>http_lan_text('application script','aplikační skript'),
      'md'=>http_lan_text('markdown text','markdown text')
     );
     $this->def_s_p=array(
      'OWN'=>http_lan_text('owner','vlastník'),
      'MANAGE'=>http_lan_text('administrator','správa'),
      'EDIT'=>http_lan_text('editor','editace'),
      'VIEW'=>http_lan_text('reader','prohlížení')
     );
     $dv='';
     foreach ($this->def_s_p as $kl=>$value){
       $dv.=($dv == ''?'':',').$kl.'='.$this->def_s_p[$kl];
     }
     $this->dv='static '.$dv;
     $this->def_la=http_lan_text(
       'static none=none,left=left,right=right,center=center,'.
       'line=line,hide=hide,point=shortcut',
       'static none=nezáleží,left=vlevo,right=vpravo,center=uprostřed,'.
       'line=na řádku,hide=zabaleno,point=zástupce');
     $this->erh=http_lan_text('Error','Chyba');
     $this->item=0;
  }
  
  /** desctructor 
  */
  function __destruct(){
    unset($this->tree);    
  } 

  /**  check if the user inserted correct password/username combination
   *   three methods are availbable -
   *  @param bool $ldap label, when LDAP login is used 
   */ 
  function check_login($ldap=false){
    
    /* v pripade spravne vyplneneho hesla vrati identifikacni cookie, ktery slouzi pro autorizaci */
    $ldap_server=MIC_LDAP_SERVER; /* server autorit hesel IP: 11-nts1 ,28-devkl nebo false */   
   
    if ($ldap){
      /* try LDAP */
      $conn=ldap_connect($ldap_server,389);
      if(!$conn){
        htpr("LDAP Server neni dostupny");
        return '';
      }
      $testhesla=false;
      $ldap_user="cn=".getpar('NAME').", cn=users, dc=cgu, dc=cz";
      $ldap_pass=getpar('PASS'); 
      $testhesla=@ldap_bind($conn,$ldap_user,$ldap_pass);  
        /* @ potlaci varovani ldap_bind(): Unable to bind to server: Invalid credentials */
        /* chyba by jinak byla zpracovana v lib.php, kde je definovany set_error_handler */
      //deb($testhesla);
      ldap_close($conn);
      if ($testhesla){ /* pri uspesnem sparovani heslo/uzivatel vraci 1 */
        return strtoupper(getpar('NAME'));
      }       
    }
    
    /* use password file - sha1 imprints */
    $pwdf='data/passwd';
    if (is_file($pwdf)){
      $li=file($pwdf);$l=array();
      for($i=0;$i<count($li);$i++) {
         $lp=explode(':',self::remcr($li[$i]));
         $l[$lp[0]]=$lp[1];
      }
    }
      
    /* in password file are stored password imprints sha1 : php -r echo(sha1('heslo'));*/
    setpar('NAME',strtolower(getpar('NAME')));
    if (getpar('NAME') && isset($l[getpar('NAME')])){
      if (strcmp(sha1(getpar('PASS')),$l[getpar('NAME')])==0){
        /* if there is a match entered and stored imprints , retrun a user */
        //$this->user=strtoupper($DATA['NAME']);
        return strtoupper(getpar('NAME'));
      }
    }
    
    /* try local database - sha1 imprints */
    $pas=$this->db->SqlFetch("select lheslo from ".$this->table."_uziv ".
                             "where ljmeno='".strtoupper(getpar('NAME'))."'");  
    if (strcmp(sha1(getpar('PASS')),$pas)==0){
      return strtoupper(getpar('NAME'));
    }
    
    /* all attempts were lost */        
    htpr('Neplatný vstup .') ;
    return '';
  }

  function process_logout(){
    /* odstrani uzivatele ze session promenne */
    $_SESSION['uzivatel']='';
    htpr('Odhlášeno. ',br(),br(),ahref('?','Přihlásit'));
    return true;
  }

  static function remcr($vstup){
    /* odstrani odradkovani z radku nacteneho souboru - pro passwd */
    return str_replace("\t",'',str_replace("\n",'',str_replace("\r",'',$vstup)));
  }

  /**  MCMS Tree getter
   */ 
  function getTree(){
    return $this->tree;
  }

  /** internal function for tree parsing 
   * @param array flat database table indexing from 1, 
   * @param int $n: current item
   * @param bool $leftside - if tree is generating for left-side menu, the item of current node is added
   * */
  private function ret_child($a,$n,$leftside){
    $b=array();
    if ($leftside){
      /* tree acceptable for left-side sidebar */
      /* sub-folders */
      $ch=array(); /* array $a as table result is indexed from 1 */
      for ($k=1;$k<=count($a);$k++){
        if ($a[$k]['ID_UP']==$n){
          $ch[$a[$k]['ID']]=$this->ret_child($a,$a[$k]['ID'],$leftside);
        }
        if ($a[$k]['ID']==$n){
          $b['name']=$a[$k]['ZKR_NAZEV'];
          $b['order']=$a[$k]['PORADI'];
        }    
      }
      $b['href']='?item='.$n;
      if (count($ch)){
        $b['child']=$ch;
      }
    }else{
      /* tree acceptable for upper menu widget */
      for ($k=1;$k<count($a);$k++){
        if ($a[$k]['ID_UP']==$n){
          $b[$a[$k]['ZKR_NAZEV']]=$this->ret_child($a,$a[$k]['ID'],$leftside);
        }    
      }
      if (count($b)==0) { /* no child - final item - return link to it */
        if (isset($a[$n])) $b=array('href'=>'?item='.$n);
      }    
    }  
    return $b;
  }

  /** returns menu object based on the database menu tree table   
   * @param bool $leftside - true: tree for sidebar, false: tree for upper menu widget
   * @return array 
   */
  function generateMenuTree($leftside){
    $table_strom=$this->table.'_strom';
    $table_prava=$this->table.'_prava';
    $table_uskup=$this->table.'_uskup';
    $user=$this->user;
    $a=to_array(
      "select id,id_up,zkr_nazev,panazev,poradi ".
      "from $table_strom ".
      "where id in (".
      " select distinct id from $table_prava ". 
      " where ". 
      "  (uzivatel=:uzivatel ". 
      "   or skupina in (select skupina from $table_uskup where uzivatel=:uzivatel )".
      "   or skupina='ALL') ".
      "  and privilege in ('VIEW','OWN','EDIT') ".
      "  and objekt='FOLDER' ".
      " ) ".   
      "order by id_up,poradi asc",
      $this->db,
      array(':uzivatel'=>$user));  
    $this->tree= $this->ret_child($a,0,$leftside);
    return 0;      
  }

  /** tree traverse
   * @param mixed tree
   * @param integer item
   * @return array items
   */  
  private function traverse($t,$item){
    foreach($t as $k=>$v){
      if ($k==$item){
        return array(array($k,$v['name']));
      }else{
        if (isset($v['child']) && is_array($v['child'])){
          $subtree=$this->traverse($v['child'],$item);
          if (count($subtree)>0){
            array_push($subtree,array($k,$v['name']));
            return $subtree;
          }
        }       
      }
    }
    return array();
  }
  
  /** get nodes
   * @param integer pageitem
   * @return array nodes
   * It returns all nodes from the root to the item  (=path)
   */
  function getNodes($item){
     $t=$this->tree;
     if (isset($t['child'])){
       $a=$this->traverse($t['child'],$item);
     }else{
       $a=array();
     }
     $a=array_reverse($a);
     return $a;
   }

  /** Breadcrumb navigation bar
  * @param $item current item
  * @return string
  */
  function breadCrumb($item){
    $a=$this->getNodes($item);
    $pom='class="breadcrumb-item"';
    $s=''; 
    for($i=0;$i<count($a);$i++){
       if ($a[$i][0]==$item){
         $s.=tg('li','class="breadcrumb-item active" aria-current="page"',$a[$i][1]);
       }else{ 
         $s.=tg('li','class="breadcrumb-item"',ahref('?item='.$a[$i][0],$a[$i][1]));
       }  
    }
    
    if ($s==''){
      /* when link is not accessible .. */
      $s=$s=tg('li', $pom,ahref('?','Úvod'));
    }elseif ( $a[0][0]!=1) {
      /* Home link when not first page on the path */
      $s=tg('li', $pom,ahref('?','Úvod')).$s;
    }  
    return tg('nav','aria-label="breadcrumb" class="nav justify-content-left p-0" ',
            tg('ol','class="breadcrumb p-2 m-2"',$s));
  }
  
  /** returns root node
   * @param integer item
   * @return integer root node item
   * 
   */
  function rootNode($item){
    $a=$this->getNodes($item);
    return (count($a)<1)?0:$a[0][0];
  }
  
  /** It returns menu object based on the database menu tree table
   *  A CMS folder rendering method. 
   *  @param integer folder ID
   *  @return bool 
   */

  function folder($item){
    if ($this->debug){ 
      //deb($item);  
      return true;  /* debug only */
    }
    $this->item=$item;  
    $table=$this->table.'_polozky';
    $table_prava=$this->table.'_prava';
    $table_uskup=$this->table.'_uskup';
    $user=$this->user;
    /* zpracovani udalosti editace slozky a editace polozky ve slozce */
    if (getpar('eD')=='1'){
      if (getpar('f')=='1'){
        $this->edit_folder();
        return true;      
      } 
      if (getpar('i')=='1'){
        $this->edit_article();
        return true;
      } 
    }
    
    if (isset($_SESSION['editace']) && $_SESSION['editace']){
      $this->editBar($item,'FOLDER');
    }
    /* print folder description */
    $table_strom=$this->table.'_strom';
    $this->db->Sql("select nazev,popisek from $table_strom where id=".$item);
    $this->db->FetchRow();
    if ($this->db->Data('NAZEV')!='') htpr(ta('h3',$this->db->Data('NAZEV')));
    if ($this->db->Data('POPISEK')!='') htpr(ta('p',$this->db->Data('POPISEK')));

    /* nasledujici radky jsou z duvodu zpetne kompatability pro puvodni script ocekavajici glob. prom.*/
    if ($this->legacy){   
      global $uzivatel;  //toto jen kvuli ladeni
      $uzivatel=$user;
    }  
   
    /* zjisti id tech polozek, ktere lze editovat */
    $editable=array();
    $this->db->Sql(
      "select id from $table where id_up=:item and ".
      "id in (".
      " select distinct id from $table_prava ". 
      " where ". 
      "  (uzivatel=:uzivatel ". 
      "   or skupina in (select skupina from $table_uskup where uzivatel= :uzivatel )".
      "   or skupina='ALL') ".
      "  and privilege in ('OWN','EDIT') ".
      "  and objekt='ITEM' ".
      " ) ".   
      "order by id_up,poradi asc",
      array(
        ':uzivatel'=>$user,
        ':item'=>$item));
    while ($this->db->FetchRow()){
      $editable[$this->db->Data('ID')]=true;
    }
    /* cyklus pres polozky, ktere se maji zobrazovat / vykonavat
     * seznam polozek se vygeneruje do pole, aby se uvolnil databazovy dotaz pro dalsi dotazovani 
     * v jednotlivych polozkach
     */          
    $a=to_array(
        "select id,typ_polozky,nazev,zkr_nazev,popisek,poradi,zarovnani ".
        "from $table ".
        "where id_up=$item and ".
        "id in (".
        " select distinct id from $table_prava ". 
        " where ". 
        "  (uzivatel=:uzivatel ". 
        "   or skupina in (select skupina from $table_uskup where uzivatel=:uzivatel )".
        "   or skupina='ALL') ".
        "  and privilege in ('VIEW','OWN','EDIT') ".
        "  and objekt='ITEM' ".
        " ) ".   
        "order by id_up,poradi asc",
        $this->db,
        array(':uzivatel'=>$user));
    /*
      nyni jiz CMS nebude pracovat s databazi - jednotlive podrizene skripty mohou otevrit tutez DB,
      pokud to potrebuji
    */
    //$this->db->Close(); //zmena 3.2.2021: pokud ji cms sam neotevira, nemel by ji ani zavirat, nelze pak uzit dalsi metody.
    
    foreach ($a as $D){
      if (isset($_SESSION['editace']) && $_SESSION['editace']){
        $this->editBar($item,'ITEM',$D);
      }
      switch ($D['TYP_POLOZKY']){
        case 'app':
          set_error_handler("Cm::errorHandler", E_ALL ); /*E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED); #E_STRICT);*/
          try{
            //deb($D['POPISEK']);
            $D['POPISEK']=str_replace('require','include',$D['POPISEK']); /* require hands pre-compile PHP system core */
            eval($D['POPISEK']);
            /* neni @eval($D['POPISEK']) */
          }catch (Exception $e){
            /*return false;*/
            deb($e);
          }  
          //restore_error_handler();
          break;
        case 'text': htpr($D['NAZEV']!=''?ta('h3',$D['NAZEV']):'',$D['POPISEK']);
          break;
        case 'md':
          include_once "vendor/Parsedown.php";
          htpr($D['NAZEV']!=''?ta('h3',$D['NAZEV']):'',Parsedown::instance()->text($D['POPISEK']));
          break;    
        default: htpr($item);
      }    
   }
  }

  static function errorHandler($errno, $errstr, $errfile, $errline=null, $errcontext=null){ 
    $e_notice=false; /* e-notice level errors are not printed */
    $e_general=false; /* general errors are not printed */
    if (!is_string($errcontext)){
      $errcontext=preg_replace("/pwd\=(.+)/",'pwd=****',print_r($errcontext,true));
    }
    switch ($errno) {
      case E_NOTICE:
        if ($e_notice) {
          deb("[Error:$errno:$errstr; file:$errfile; column:$errline \n");
        } 
        break; 
      default:
        if ($e_general) {  
          deb("[Error:$errno:$errstr; file:$errfile; column:$errline context:$errcontext]\n");
        }
        break;
    }  
    return true;
  }

  /** Returns name and surname of the logged user
   * @param string  user login name 
   * @return string user name and surname
   */
  function getUserInfo($user){
    $table=$this->table.'_uziv';
    $user=strtoupper($user);
    $this->db->Sql(
     "select jmeno||' '||prijmeni as JM ".
     "from $table where ljmeno=:uzivatel ",
     array(':uzivatel'=>$user));
    return $this->db->FetchRow()?$this->db->Data('JM'):'-';   
  }

  /** recursive menu tree submethod - see method sidebar
   * @param array menu tree
   * @param integer item 
   * @param integer deep
   * @param integer rootnode
   * @param object cms
  */
  static function sidebar_part($tp,$item,$deep,$rootnode,$cms){
    //deb($tp);
    $s='';
    if (isset($tp['child'])){
      foreach($tp['child'] as $it=>$value){
        $current=($value['href']==('?item='.$item));
        if ($deep==0){
          $s.= tg('div','',
                ahref($value['href'],$value['name'],'class="list-group-item list-group-item-action bg-ligth'.
                      ($current?' active':'').'" ').                //tg('span','class="tree-toggler"','+').
                self::sidebar_part($value,$item,$deep+1,$rootnode,$cms));
        }else{
           if ($cms->rootNode($it)==$rootnode) { /* rozbaluje se jen aktivni cast stromu od korene */
            $s.=ahref($value['href'],
                     $value['name'],
                     'class="nav-link ml-'.floor($deep).' my-0'.
                      ($current?' active':'').'"').
            self::sidebar_part($value,$item,$deep+1,$rootnode,$cms);
           } 
        }
      }
    }
    return ($s!='')?tg('nav','class="nav nav-pills flex-column"',$s):'';
  }
  
  /** sidebar generator - left menu tree from the root
   * @param array $t menu tree
   * @param integer $item 
   * @param integer $rootnode
   * @param object $cms
   * @param string $addings
   * @param string $add_style (f.e. 'style="background-color:#FAFFFA"' )
   */ 
  function sidebar($t,$item,$rootnode,$cms,$addings='',$add_style=''){
    $s=self::sidebar_part($t,$item,0,$rootnode,$cms); /* do prvni iterace se preda cely strom */
    
    return tg('div','class="bg-light border-right" id="sidebar-wrapper" ',"\n".
            tg('div','class="sidebar-heading" '.$add_style,$cms->getUserInfo($this->user).$addings).
            tg('div','class="list-group list-group-flush" '.$add_style,$s)
           );
  } 
  
  /** Genetare HTML link for current page editor - or link for quit the editor mode
   *  When user cannot modify the page, it generates an empty string.
   *  @param integer item - current page 
   *  @return string anchor HTML element
   */ 
  function editLink($item){
    $table_prava=$this->table.'_prava';
    $table_uskup=$this->table.'_uskup';
    $this->db->Sql("select privilege from $table_prava where id=$item ".
       "and objekt='FOLDER' ".
       "and (uzivatel=:uzivatel ". 
       "     or skupina in (select skupina from $table_uskup where uzivatel=:uzivatel)".
       "     or skupina='ALL') ".
       "and privilege in ('OWN','EDIT')",
      array(':uzivatel'=>$this->user));
    if ($this->db->FetchRow()){
      if (isset($_SESSION['editace']) && $_SESSION['editace']){
        return ahref('?item='.$item.'&amp;ed=0','Zobrazení','class="nav-link"');
      }else{
        return ahref('?item='.$item.'&amp;ed=1','Editace','class="nav-link"');
      }  
    }
    $_SESSION['editace']=0;   
    return '';
  } 
  
  /** Generate HTML edit bar 
   *  @param integer item - current item to edit
   *  @param string type of the item
   *  @return string anchor HTML element 
   */
  function editBar($item,$type,$D=array()){
    if ($type=='FOLDER'){
      htpr(tg('div','class="errors"',
       http_lan_text('Folder','Složka').' '.$item.',['.$this->user.']: '.
       ahref('?eD=1&amp;f=1&amp;item='.$item,
        bt_icon('pencil').http_lan_text('Folder properties','Vlastnosti této složky')).nbsp(2).
       ahref('?eD=1&amp;f=1&amp;new=1&amp;item='.$item.'&amp;id_up='.$item,
        bt_icon('caret-down').http_lan_text('Create Folder','Nová podsložka')).nbsp(2).
       ahref('?eD=1&amp;i=1&amp;new=1&amp;item='.$item.'&amp;id_up='.$item,
        bt_icon('plus').http_lan_text('Create item','Nová položka')).nbsp(2)
       ));
    }
    if ($type=='ITEM'){
      htpr(tg('div','class="errors"',
         ahref('?eD=1&amp;i=1&amp;eitem='.$D['ID'].'&amp;item='.$item,
         bt_icon('pencil').
         $D['ID'].':'.$D['ZKR_NAZEV'].'['.$D['TYP_POLOZKY'].']' //.
         //(verejne($D['ID'],'ITEM')?'':'<img src="img/lock_icon.png" alt="[soukromé]">')
         )));
    }
  }
  
  /** Edit folder form method
   * @return bool true
   */
  function edit_folder(){
    htpr(tg('div','class="errors"',
      ahref('?item='.getpar('item'),
            http_lan_text('Return to folder','Návrat do složky')))
    );
    $table_strom=$this->table.'_strom';
    $table_prava=$this->table.'_prava';
    $table_uskup=$this->table.'_uskup';
    $def_usporadani=http_lan_text(
     'static order=order,template=template,time=time',
     'static order=podle pořadí,template=šablonou,time=podle data změny');
    $DB=array(
     'ID_UP'=>getpar('id_up'),
     'NAZEV'=>'', 
     'ZKR_NAZEV'=>'',
     'PANAZEV'=>'', 
     'PORADI'=>'', 
     'POPISEK'=>'',
     'ZAROVNANI'=>'',
     'NAZEV_E'=>'',
     'ZKR_NAZEV_E'=>'',
     'POPISEK_E'=>'');
    
    if (getpar('U')!=''){
       $this->update_folder();
    }
    if (getpar('D')!=''){
       $nid=$this->delete_folder();
       if ($nid>=0){
         /* the folder was removed, construct a link to the parent folder */
          htpr(ahref('?item='.$nid,http_lan_text('Go to the parent folder','Otevřít nadřízenou složku')));
          return true;   
       }
       /* otherwise do nothing there */
    }
    if (getpar('I')!=''){
       $nid=$this->insert_folder();
       if ($nid>=0){
         /* the folder has just been created: move to it in next step */
         /* it is not posssible do it immediatelly, because navigation menu is not updated yet */
         htpr(ahref('?item='.$nid,http_lan_text('Go to the new folder','Otevřít vytvořenou složku')));
         return true;
       }else{
         /* vloz neulozena data do $D */
         $DB=getpars();
         //deb($D);
         setpar('new',1);
       }
    }
    if (getpar('DA')!=''){
       $this->deleteFolderAccessProp();
    }
    if (getpar('IA')!=''){
       $this->insertFolderAccessProp();
    }
    
    if (getpar('new')!=1){
      /* editace existujici slozky - dotahni data*/
      if (!$this->afterEdit){
        //$DB=to_array('select * from '.$table_strom.' where id='.getpar('item'),$this->db)[1];
        $pom=to_array(
              "select * from $table_strom where id=:id ",
              $this->db,
              array(':id'=>getpar('item')));
        $DB=$pom[1];
      }
      $head_text=http_lan_text('Folder editing','Editace složky');
    }else{
      /* nova slozka - je dana jedine jeji nadrazena slozka*/    
      $head_text=http_lan_text('New Folder','Nová složka');
    }
    if (getpar('new')==1){
      $DB['SLOUPCU']=1;
    }
    $sql_co="select id,zkr_nazev from $table_strom ".
     "where (id in (select id from $table_prava where ". 
                   " (skupina in (select skupina from $table_uskup where uzivatel=:u) ".
                   " or uzivatel=:u ) ".
     " and privilege in ('MANAGE','EDIT','OWN') and objekt='FOLDER') ".
     " and id<>:item ".  /* folder cannot be itself child ! */
     " ) or id=:id_up ".
     " or id_up=-1 ". /* root has id_up=-1 */
     "order by nazev";
    htpr(tg('div',' ',
      ta('h3',$head_text).
      tg('form','action="'.$_SERVER['SCRIPT_NAME'].'"',
       tg('table','border="0"',
        trtd().http_lan_text('Folder ID:','ID složky:').tdtd().
         (getpar('new')?('['.http_lan_text('not yet','zatím není').']'):getpar('item')).
         para('item',getpar('item')).nbsp(5).
         (getpar('new')?para('new',1):'').
         ($this->canManage($DB['ID_UP'],'FOLDER')?
          lov(http_lan_text('Location','Umístění'),
           'ID_UP',
           $this->db,
           array($sql_co,array(':u'=>$this->user,':item'=>getpar('item'),':id_up'=>$DB['ID_UP'])),
           $DB['ID_UP']):
          (para('ID_UP',$DB['ID_UP']).http_lan_text('Parent folder id','ID nadřízené složky').': '.$DB['ID_UP'])
         ).trow().
        trtd().http_lan_text('Folder location:','Umístění').tdtd().
          (getpar('item') != ''?$this->itemToPath(getpar('item')):
           $this->itemToPath($DB['ID_UP'])).
         nbsp(3).
         (ahref('?ed=1&amp;uu='.$this->itemToPath($DB['ID_UP']).
          http_lan_text('Link to parent folder','Odkaz na nadřazenou složku'))).trow().
        trtd().textfield(http_lan_text('Internal path','Cesta').tdtd(),'PANAZEV',20,60,$DB['PANAZEV']).
         ' '.http_lan_text('Alphanumeric characters part of URL',
         '(alfanum. znaky , část URL)').trow().
        trtd().textfield(ta('b',http_lan_text('Title','Název')).tdtd(),'NAZEV',60,255,$DB['NAZEV']).trow().
        trtd().textfield(http_lan_text('Short title','Krátký název').' '.tdtd(),'ZKR_NAZEV',20,20,$DB['ZKR_NAZEV']).
         textfield(' '.http_lan_text('Order','Pořadí').' ','PORADI',2,4,$DB['PORADI']).
         lov(http_lan_text('Sort rule','Uspořádání ').' ','ZAROVNANI','',$def_usporadani,$DB['ZAROVNANI']).
         textfield(http_lan_text('Columns','Sloupců'),'SLOUPCU',1,2,$DB['SLOUPCU']).trow().
        trtd(2).textarea(http_lan_text('Folder description','Popisný text ke složce').br(),
               'POPISEK',15,90,$DB['POPISEK']).trow().
        trtd(2).
         bt_hidable_area('Anglicky','engl',
          //ahref("javascript:switchContent('eng')",'Anglicky'.bt_icon('chevron-down')).
          //tg('div','id ="eng" style="display:none;"',     
           //obalka_zacatek('eng',http_lan_text('','Anglicky')),
            textfield(ta('b',http_lan_text('English title','Anglický název')),
             'NAZEV_E',60,255,$DB['NAZEV_E']).br().
            textfield(http_lan_text('English short title','Anglický krátký název '),
             'ZKR_NAZEV_E',20,20,$DB['ZKR_NAZEV_E']).br().
            textarea(http_lan_text('English description of the folder','Anglický popisný text ke složce').br(),
             'POPISEK_E',15,90,$DB['POPISEK_E']).
          br()
          //.ahref("javascript:switchContent('eng')",bt_icon('chevron-down'))
          ).trow().
         trtd(2).
           submit(getpar('new')=='1'?'I':'U',
                  getpar('new')=='1'?http_lan_text('Insert','Vložit'):http_lan_text('Save','Uložit'),
                  'btn btn-primary'
                  ).
          nbsp(15).
          ((getpar('new')=='')?submit('D',http_lan_text('Delete','Smazat'),'btn btn-outline-primary'):
          nbsp(1)).
          para('new',getpar('new')).
          para('f',1).
          para('eD',1).trow()
       ))
     ));
    if (getpar('new')==''){
      if ($this->canManage(getpar('item'))){
        htpr(br(),$this->folderAccessProp(getpar('item')));
      }else{
        htpr(http_lan_text('Without the possibility of the editing the access rights.',
                         'Bez možnosti měnit přístupová práva.'));
      }
    }
    return true;
  }
  
  /** Edit folder form method
   * @return integer return code
   */
  function insert_folder(){
    $this->err=$this->check('folder');
    if ($this->err!='') {
      htpr(bt_dialog('Chyba',$this->err));
      setpar('NEW',1);    
      return -1;
    }
    $table_strom=$this->table.'_strom';
    $table_prava=$this->table.'_prava';  
    /* generate next id - simple approach - it can be replaced by sequence */
    $next_id=$this->db->SqlFetch("select max(id)+1 as m from $table_strom");
    /* zatim je to delene, protoze SQLite nevykonava druhy prikaz v bloku, kdyz je bind  */
    if ($this->db->typedb=='oracle'){
      $sql=$this->begin.
      "insert into $table_strom ".
               "(id, id_up, nazev, zkr_nazev, panazev, poradi, popisek, sloupcu, zarovnani, nazev_e, zkr_nazev_e, popisek_e, dbuser, dbdatum) ".
      "values  (:id,:id_up,:nazev,:zkr_nazev,:panazev,:poradi,:popisek,:sloupcu,:zarovnani,:nazev_e,:zkr_nazev_e,:popisek_e,:dbuser, ".$this->sysdate."); ".
      "insert into $table_prava (id,objekt,skupina,uzivatel,privilege,dbuser,dbdatum) ".
      " values (:id,'FOLDER','NONE',:uzivatel,'OWN',:dbuser,".$this->sysdate."); ".
      $this->end;
      $bind=array(
       ':id_up'=>getpar('ID_UP'),
       ':nazev'=>getpar('NAZEV'),
       ':zkr_nazev'=>getpar('ZKR_NAZEV'),
       ':panazev'=>getpar('PANAZEV'),
       ':poradi'=>getpar('PORADI'),
       ':popisek'=>getpar('POPISEK'),
       ':sloupcu'=>getpar('SLOUPCU'),
       ':zarovnani'=>getpar('ZAROVNANI'),
       ':nazev_e'=>getpar('NAZEV_E'),
       ':zkr_nazev_e'=>getpar('ZKR_NAZEV_E'),
       ':popisek_e'=>getpar('POPISEK_E'),
       ':dbuser'=>$this->user,
       ':uzivatel'=>$this->user,
       ':id'=>$next_id
      );
    
      if ($this->db->Sql($sql,$bind)){
       htpr(bt_dialog('Chyba','Data nebyla uložena.'));
       setpar('NEW',1);
       return -1;
      }else{
       //htpr('Uloženo.','Data uložena.');
       htpr(bt_dialog('Uloženo.','Data uložena.'));
       return $next_id;
      }
    }
    if ($this->db->typedb=='sqlite'){
      $sql1="insert into $table_strom ".
                "(id, id_up, nazev, zkr_nazev, panazev, poradi, popisek, sloupcu, zarovnani, nazev_e, zkr_nazev_e, popisek_e, dbuser, dbdatum) ".
       "values  (:id,:id_up,:nazev,:zkr_nazev,:panazev,:poradi,:popisek,:sloupcu,:zarovnani,:nazev_e,:zkr_nazev_e,:popisek_e,:dbuser,".$this->sysdate."); ";
      $bind1=array(
        ':id_up'=>getpar('ID_UP'),
        ':nazev'=>getpar('NAZEV'),
        ':zkr_nazev'=>(string)getpar('ZKR_NAZEV'),
        ':panazev'=>getpar('PANAZEV'),
        ':poradi'=>getpar('PORADI'),
        ':popisek'=>getpar('POPISEK'),
        ':sloupcu'=>getpar('SLOUPCU'),
        ':zarovnani'=>getpar('ZAROVNANI'),
        ':nazev_e'=>getpar('NAZEV_E'),
        ':zkr_nazev_e'=>getpar('ZKR_NAZEV_E'),
        ':popisek_e'=>getpar('POPISEK_E'),
        ':dbuser'=>$this->user,
        ':id'=>$next_id
       ); 

      $sql2="insert into $table_prava (id,objekt,skupina,uzivatel,privilege,dbuser,dbdatum) ".
       " values (:id,'FOLDER','NONE',:uzivatel,'OWN',:dbuser,".$this->sysdate."); ";
      $bind2=array(
       ':dbuser'=>$this->user,
       ':uzivatel'=>$this->user,
       ':id'=>$next_id);
      if ($this->db->Sql($sql1,$bind1)){
        $er=true;
      }elseif ($this->db->Sql($sql2,$bind2)){
        $er=true;
      }else{
        $er=false;
      }
      if ($er){
       htpr(bt_dialog('Chyba','Data nebyla uložena.'));
       setpar('NEW',1);
       return -1;
      }else{
       //htpr('Uloženo.','Data uložena.');
       htpr(bt_dialog('Uloženo.','Data uložena.'));
       return $next_id;
      } 
    }  
  }
  
  function update_folder(){
    $table_strom=$this->table.'_strom';
    $item=getpar('item');
    $sql="update $table_strom set ".
      "id_up=:id_up,".
      "nazev=:nazev,".
      "zkr_nazev=:zkr_nazev,".
      "panazev=:panazev,".
      "poradi=:poradi,".
      "popisek=:popisek,".
      "sloupcu=:sloupcu,".
      "zarovnani=:zarovnani,".
      "nazev_e=:nazev_e,".
      "zkr_nazev_e=:zkr_nazev,".
      "popisek_e=:popisek_e,".
      "dbuser=:dbuser,".
      "dbdatum=".$this->sysdate.
      " where id=:id";
     $bind=array(
      ':id_up'=>(integer)getpar('ID_UP'),
      ':nazev'=>(string)getpar('NAZEV'),
      ':zkr_nazev'=>(string)getpar('ZKR_NAZEV'),
      ':panazev'=>(string)getpar('PANAZEV'),
      ':poradi'=>(integer)getpar('PORADI'),
      ':popisek'=>(string)getpar('POPISEK'),
      ':sloupcu'=>(integer)getpar('SLOUPCU'),
      ':zarovnani'=>(string)getpar('ZAROVNANI'),
      ':nazev_e'=>(string)getpar('NAZEV_E'),
      ':zkr_nazev_e'=>(string)getpar('ZKR_NAZEV_E'),
      ':popisek_e'=>(string)getpar('POPISEK_E'),
      ':dbuser'=>$this->user,
      ':id'=>$item);  
    if ($this->db->Sql($sql,$bind)){
       htpr(bt_dialog($this->erh,http_lan_text('Data was not saved.','Data nebyla uložena.')));
    }else{
       htpr(bt_dialog('Uloženo.',http_lan_text('Data was saved.','Data uložena.')));
    }
  }
  
  function delete_folder(){
    $table_strom=$this->table.'_strom';
    $table_polozky=$this->table.'_polozky';
    $table_prava=$this->table.'_prava';
    $item=getpar('item');
    /* kontrola, zda nejsou podrizene polozky. V takovem pripade nelze smazat */
    $this->db->Sql("select count(id) as pocet from $table_strom where id_up=:item", array(':item'=>$item));
    $this->db->FetchRow();
    if ($this->db->Data('POCET')>0){
      htpr(bt_dialog($this->erh,http_lan_text('Cannot delete - there are subfolders','Nelze smazat - jsou podřízené složky')));
      return -1;
    }
    $this->db->Sql("select count(id) as pocet from $table_polozky where id_up=:item", array(':item'=>$item));
    $this->db->FetchRow();
    if ($this->db->Data('POCET')>0){
      htpr(bt_dialog($this->erh,http_lan_text('Cannot delete - there are sub-items.','Nelze smazat - jsou podřízené položky')));
      return -1;
    }
    $this->db->Sql("select count(id) as pocet from $table_prava where id=:item and objekt='FOLDER'",array(':item'=>$item));
    $this->db->FetchRow();
    if ($this->db->Data('POCET')>1){
      htpr(bt_dialog($this->erh,http_lan_text('Remove foreign access rights.','Odstraňte cizí přístupová práva.')));
      return -1;
    }    
    
    /* */
    $id_up=$this->db->SqlFetch("select id_up from $table_strom where id=:item", array(':item'=>$item));
    $sql1="delete from $table_strom where id=:item; ";
    $sql2="delete from $table_prava where id=:item and objekt='FOLDER'; ";
    $bind=array(':item'=>(integer)$item);         
    $er=$this->db->Sql($sql1, $bind);
    if ($er){
       htpr(bt_dialog(http_lan_text('Error','Chyba'),
            http_lan_text('Folder was not removed.','Složka nebyla smazána.')));
       return -1;
    }else{
      $er=$this->db->Sql($sql2, $bind);
      if ($er){
        htpr(bt_dialog(http_lan_text('Error','Chyba'),
            http_lan_text('Folder was not removed.','Složka nebyla smazána.')));
         return -1;
      }else{
          htpr(bt_dialog(http_lan_text('Removed','Odstraněno'),
          http_lan_text('Folder was removed.','Složka byla smazána.')));
          return $id_up;
      }
    }         
  }
  
  function deleteFolderAccessProp(){
    $table_prava=$this->table.'_prava';
    $item=getpar('item');
    $u=getpar('uu');
    $p=getpar('p');
    $g=getpar('g');
    if ($u==$this->user && $p=='OWN'){
      htpr(bt_dialog('Nelze odstranit','Toto oprávnění nemůžete odstranit samostatně. Smažte položku.'));
      return;
    }
    $sql="delete from $table_prava where id=:item and objekt='FOLDER' ".
     "and trim(skupina)='$g' and trim(uzivatel)='$u' and privilege='$p' ";
    if ($this->db->Sql($sql,array(':item'=>$item))){
       htpr(bt_dialog(http_lan_text('Error','Chyba'),http_lan_text('Not deleted.','Data nebyla smazána.')));
    }else{
       //htpr(bt_dialog('Uloženo.','Data uložena.'));
    }    
  }
  
  function insertFolderAccessProp(){
    $table_prava=$this->table.'_prava';
    $item=getpar('item');
    $u=getpar('UZIVATEL');
    $p=getpar('PRIVILEGE');
    $g=getpar('SKUPINA');
    if ($u.$g=='' || $p=='') return; /* nothing to do whne empty */
    if ($u!='' && $g!='') $g='';     /* prefer user rights before group */
    if ($u=='') $u='NONE';
    if ($g=='') $g='NONE';
    $sql="insert into $table_prava (id,objekt,skupina,uzivatel,privilege,dbuser,dbdatum) ".
         "values (:item,'FOLDER',:g, :u, :p ,'".$this->user."',".$this->sysdate.")";
    $bind=array(':item'=>$item,':g'=>$g,':u'=>$u,':p'=>$p);     
    if ($this->db->Sql($sql,$bind)){
       htpr(bt_dialog('Chyba','Data nebyla uložena.'));
    }else{
       //htpr(bt_dialog('Uloženo.','Data uložena.'));
    } 
  }
  
  /** Method for the arcticle properties
   * 
   */
  function edit_article(){
    $table_polozky=$this->table.'_polozky';
    $table_strom=$this->table.'_strom';
    $table_prava=$this->table.'_prava';
    $table_uskup=$this->table.'_uskup';
    
    $typ=getpar('type');
    $D=array('TYP'=>$typ,
     'ID_UP'=>getpar('item'),
     'NAZEV'=>'', 
     'ZKR_NAZEV'=>'', 
     'PORADI'=>'', 
     'POPISEK'=>'',
     'NAZEV_E'=>'',
     'ZKR_NAZEV_E'=>'',
     'POPISEK_E'=>'', 
     'ZAROVNANI'=>'');
   
    htpr(tg('div','class="errors"',
      ahref('?item='.getpar('item'),
            http_lan_text('Return to folder','Návrat do složky'))));
    if (getpar('U')!=''){
       $this->update_article();
    }
    if (getpar('I')!=''){
       $nid=$this->insert_article();
       if ($nid>=0){
         /* znalost klice polozky zpusobi nacteni vety z DB */
         setpar('eitem',$nid);
       }else{
         /* vloz neulozena data do $D */
         $D=getpars();
         //deb($D);
         setpar('new',1);
       }
    }
    if (getpar('D')!=''){
       $this->del_article_conf();
    }
    if (getpar('DD')!=''){
       $err=$this->del_article();
       if ($err){
         htpr(bt_dialog(http_lan_text('Error','Chyba'),
                        http_lan_text('Record was not deleted.','Chyba při mazání záznamu')));
       }else{
         htpr(bt_dialog(http_lan_text('Deleted','Smazáno'),
                        http_lan_text('Item was removed.','Položka odstraněna.')));
         return;
       }
    }
    if (getpar('DA')!=''){
       $this->deleteArticleAccessProp();
    }
    if (getpar('IA')!=''){
       $this->insertArticleAccessProp();
    }
               
    if (getpar('eitem')!=''){
      $eitem=getpar('eitem');
      $this->db->Sql("select * from $table_polozky where id=$eitem");
      $this->db->FetchRow();
      $D=$this->db->DataHash();
      $typ=$D['TYP_POLOZKY'];
    }else{        
      if (getpar('type')==''){
        $typy='';  
        foreach ($this->typy as $klic => $value){ 
          $typy.=(($typy != '')?',':'').$klic.'='.$value;
        }
        $typy='static '.$typy;

        /* formular pro zjisteni typu polozky */
        htpr(tg('form','action="'.$_SERVER['SCRIPT_NAME'].'"',
             gl(
             ta('h3',http_lan_text('Choose the item type','Zvolte typ nové položky')),
             lov(http_lan_text('Item type','Typ položky').' :','type','',$typy,'text'),
             para('id_up',getpar('id_up')),
             para('item',getpar('item')),
             para('new',1),
             submit('NEW',http_lan_text('Next','Další'),'btn btn-primary'),
             para('eD',1),
             para('i',1))));
        return 0;          
      }
      
    }
    /*  */
    $u=$this->user;
    $sql_lov=array("select id,zkr_nazev from $table_strom
     where id in (select id from $table_prava where 
     (skupina in (select skupina from $table_uskup where uzivatel=:u)
      or uzivatel=:u)
     and privilege in ('MANAGE','EDIT','OWN')
     and objekt='FOLDER') or id=:id_up order by nazev",array(':u'=>$u,':id_up'=>$D['ID_UP']));
    
    $pom=ta('h3',(getpar('NEW')!=''?http_lan_text('Insert','Vložení'):http_lan_text('Edit','Editace')).
      ' - '.$this->typy[$typ]);
    $pom.='<table border="0" width="80%">'.
     trtd().http_lan_text('Object ID','ID objektu').':'.tdtd().
      ((getpar('NEW')!='')?http_lan_text('not set so far','ještě neurčeno'):getpar('eitem')).
      para('eitem',(getpar('eitem')!='')?getpar('eitem'):'').
      para('item',getpar('item')).
      para('TYP_POLOZKY',$typ).
      para('ed',getpar('ed')).
      nbsp(5).
      (($this->canManage($D['ID_UP'],'ITEM') && (getpar('new')=='') )?
       lov(http_lan_text('Location','Umístění'),'ID_UP',$this->db,$sql_lov,getpar('item')):
       (para('ID_UP',$D['ID_UP']). 'ID složky: '.$D['ID_UP'])).
     trow();
    
    if ($D['ZKR_NAZEV']=='') $D['ZKR_NAZEV']='.';
    if ($D['PORADI']=='') $D['PORADI']=1; 
    switch ($typ){        
      case 'text':
      case 'md':     
        $pom.=
         trtd().textfield(http_lan_text('Title','Nadpis textu').tdtd(),'NAZEV',65,255,$D['NAZEV']).
         http_lan_text('If empty, no separate paragraph will be generated. ',
           'necháte-li prázdné, položka nebude mít svůj vlastní odstavec s nadpisem').trow().
         trtd().textfield(http_lan_text('Short title','Zkrácený název').tdtd(),'ZKR_NAZEV',12,12,$D['ZKR_NAZEV']).
           'zkrácený název slouží pro menu'.trow().
         trtd().textfield(http_lan_text('Order on page','Pořadí položky na stránce').tdtd(),'PORADI',3,4,$D['PORADI']).
           'přirozené číslo pro seřazení jednotlivých položek na stránce'.trow().
         trtd(2).textarea(http_lan_text('Text of the item including the HTML',
            'Vlastní text včetně HTML').br(),'POPISEK',10,110,$D['POPISEK']).trow().
         trtd(2).
         //ahref("javascript:switchContent('eng')",'Anglicky'.bt_icon('chevron-down')).
         bt_hidable_area('Anglicky','engl',
          //tg('div','id ="eng" style="display:none;"',
           gl(textfield(ta('b',http_lan_text('English title','Anglický nadpis textu')),
            'NAZEV_E',65,255,$D['NAZEV_E']).br().
            textfield(http_lan_text('English short title','Anglický zkrácený název').' ',
            'ZKR_NAZEV_E',20,20,$D['ZKR_NAZEV_E']).br().
            textarea(http_lan_text('English text including the HTML',
            'Anglický vlastní text četně HTML').br(),
            'POPISEK_E',10,110,$D['POPISEK_E'])
           )
         ).
         trow().
         trtd().lov(http_lan_text('Portrayal','Strategie zobrazení').tdtd(),'ZAROVNANI','',$this->def_la,$D['ZAROVNANI']).trow();
      break;   
      case 'app':
       $pom.=
        trtd().textfield(http_lan_text('Title','Název').tdtd(),'NAZEV',65,255,$D['NAZEV']).trow().
        trtd().textfield(http_lan_text('Short title','Zkrácený název').tdtd(),'ZKR_NAZEV',12,12,$D['ZKR_NAZEV']).trow().
        trtd().textfield(http_lan_text('Order','Pořadí').tdtd(),'PORADI',3,4,$D['PORADI']).trow().
        trtd(2).($this->ace_editor?(http_lan_text('Script code','Kód scriptu [První řádek obsahuje povinně pouze obal PHP kódu pro editor ACE - neukládá se]').br().
         tg('div','id="command" style="margin:1px; border:1px solid #888; "','&lt;'.("?php\n").@htmlspecialchars($D['POPISEK'])).
        tg('script','src="vendor/ace/src-min/ace.js" type="text/javascript"',' ').
        tg('script','',
         ' var editor = ace.edit("command");'.
         ' editor.setTheme("ace/theme/github");'.
         ' editor.getSession().setMode("ace/mode/php");'.
         ' editor.setOptions({ fontSize: "'.'10'.'"});'.
         ' function uloz(){'.
         '  document.getElementById("POPISEK").value=editor.getValue(); '.
         ' /* osekat uvodni a koncovy php tag - ten se neuklada */'.
         ' }').
       tg('style','type="text/css"','#command { height:200px;}').
       tg('input','type="hidden" name="POPISEK" id="POPISEK" value="" ','noslash')) /* obsah souboru pri post*/
      :textarea(http_lan_text('Script code','Kód scriptu').br(),'POPISEK',20,85,$D['POPISEK'],
         'style="font-family:\'Courier New\'"') ).   
        trow().
        trtd().lov(http_lan_text('Portrayal','Strategie zobrazení').tdtd(),'ZAROVNANI','',
        $this->def_la,$D['ZAROVNANI']).trow();
       break;   
     }
     $pom.='</table>';
     if (getpar('new')=='1'){
       $pom.=submit('I',http_lan_text('Insert','Vložit'),'btn btn-primary').
       para('type',$typ);     
     }else{
       $pom.=submit('U',http_lan_text('Save','Uložit'),'btn btn-primary');
     }        
     $pom.=nbsp(15).
      ((getpar('new')=='')?submit('D',http_lan_text('Delete','Smazat'),'btn btn-outline-primary'):nbsp(1)).
      para('eD',1).
      para('i',1).trow();
     htpr(tg('form',true?('name="U" onsubmit="uloz();" action="'.$_SERVER['SCRIPT_NAME'].'"'):"",$pom)); 
     if (getpar('new')==''){
      if ($this->canManage(getpar('eitem'),'ITEM')){
        htpr(br(),$this->articleAccessProp(getpar('eitem'),$typ));
      }else{
        htpr(http_lan_text('Without the possibility of the editing the access rights.',
                         'Bez možnosti měnit přístupová práva.'));
      }
    }           
   }
  
  /** insert arctile method
   * 
   */
  function insert_article(){
    $this->err=$this->check('article');
    if ($this->err!='') {
      htpr(bt_dialog('Chyba',$this->err));
      setpar('NEW',1);    
      return -1;
    }
    $table_polozky=$this->table.'_polozky';
    $table_prava=$this->table.'_prava';  
    
    /* generate next id - simple approach - it can be replaced by sequence */
    $next_id=$this->db->SqlFetch("select max(id)+1 as m from $table_polozky");
    if ($next_id=='') $next_id=1; /* at the start there is nothing in the table */
    $popisek=getpar('POPISEK');
    if ($this->ace_editor && getpar('TYP_POLOZKY')=='app' ){
      $popisek=str_replace('<'.'?'.'php','',$popisek);
    }  
    $sql1=
      "insert into $table_polozky ".
      "(id, id_up ,nazev, zkr_nazev, poradi, popisek, zarovnani, typ_polozky, nazev_e, ".
      "zkr_nazev_e, popisek_e, dbuser, dbdatum) ".
      "values (:id, :id_up, :nazev, :zkr_nazev, :poradi, :popisek, :zarovnani, :typ_polozky, :nazev_e, ".
      ":zkr_nazev_e, :popisek_e, '".$this->user."', ".$this->sysdate.") ";
    $bind1=array(
     ':id_up'=>(integer)getpar('ID_UP'),
     ':nazev'=>(string)getpar('NAZEV'),
     ':zkr_nazev'=>(string)getpar('ZKR_NAZEV'),
     ':poradi'=>(integer)getpar('PORADI'),
     ':popisek'=>(string)$popisek,
     ':zarovnani'=>(string)getpar('ZAROVNANI'),
     ':typ_polozky'=>(string)getpar('TYP_POLOZKY'),
     ':nazev_e'=>(string)getpar('NAZEV_E'),
     ':zkr_nazev_e'=>(string)getpar('ZKR_NAZEV_E'),
     ':popisek_e'=>(string)getpar('POPISEK_E'),
     ':id'=>(integer)$next_id
    );  
    $sql2="insert into $table_prava (id,objekt,skupina,uzivatel,privilege,dbuser,dbdatum) ".
      " values (:id,'ITEM','NONE','".$this->user."','OWN','".$this->user."',".$this->sysdate.") ";
    $bind2=array(
      ':id'=>(integer)$next_id);  
    if ($this->db->Sql($sql1,$bind1)){
      $er=true;
    }elseif ($this->db->Sql($sql2,$bind2)){
      $er=true;
    }else{
      $er=false;
    }
    if ($er){
       htpr(bt_dialog('Chyba','Data nebyla uložena.'));
       
       setpar('POPISEK',$popisek);
       setpar('NEW',1);
       return -1;
    }else{
       htpr(bt_dialog('Uloženo.','Data uložena.'));
       return $next_id;
    }
  } 
  
  function update_article(){
    $table_polozky=$this->table.'_polozky'; 
    $eitem=getpar('eitem');
    $popisek=getpar('POPISEK');
    if ($this->ace_editor && getpar('TYP_POLOZKY')=='app' ){
      $popisek=str_replace('<'.'?'.'php'.chr(13).chr(10),'',$popisek);
    } 
    $sql="update $table_polozky set ".
      "id_up=:id_up,".
      "nazev=:nazev,".
      "zkr_nazev=:zkr_nazev,".
      "poradi=:poradi,".
      "popisek=:popisek,".
      "zarovnani=:zarovnani,".
      "typ_polozky=:typ_polozky,".
      "nazev_e=:nazev_e,".
      "zkr_nazev_e=zkr_nazev_e,".
      "popisek_e=:popisek_e,".
      "dbuser='".$this->user."',".
      "dbdatum=".$this->sysdate.
     " where id=:id";
    $bind=array(
     ':id_up'=>(integer)getpar('ID_UP'),
     ':nazev'=>(string)getpar('NAZEV'),
     ':zkr_nazev'=>(string)getpar('ZKR_NAZEV'),
     ':poradi'=>(integer)getpar('PORADI'),
     ':popisek'=>(string)getpar('POPISEK'),
     ':zarovnani'=>(string)getpar('ZAROVNANI'),
     ':typ_polozky'=>(string)getpar('TYP_POLOZKY'),
     ':nazev_e'=>(string)getpar('NAZEV_E'),
     ':zkr_nazev_e'=>(string)getpar('ZKR_NAZEV_E'),
     ':popisek_e'=>(string)getpar('POPISEK_E'),
     ':id'=>$eitem); 
    if ($this->db->Sql($sql,$bind)){
       htpr(bt_dialog('Chyba','Data nebyla uložena.'));
    }else{
       htpr(bt_dialog('Uloženo.','Data uložena.'));
    }
  }
  
  function del_article_conf(){
    /* test for other acces rights */
    $table_prava=$this->table.'_prava';
    $eitem=getpar('eitem');   
    $c=$this->db->SqlFetch(
       "select count(id) as c ".
       "from $table_prava ".
       "where id=:eitem and objekt='ITEM'",
      array(':eitem'=>$eitem));
    
    if ($c>1){
      htpr(bt_dialog('Nelze odstranit',
       'Nejprve odstraňte cizí přístupová práva. Odstranit lze jen Vaši vlastní položku.'));
      return 0;
    }
    
    htpr(bt_fdialog('Varování',
      http_lan_text('Do you agree to remove this ['.$eitem.'] item ?',
                    'Opravdu smazat tuto položku ['.$eitem.'] ?').
      para('eD',1).para('i',1).
      para('DD',1).para('eitem', $eitem).para('item',getpar('item'))));
  }
  
  function del_article(){
    $table_polozky=$this->table.'_polozky';
    $table_prava=$this->table.'_prava';
    $eitem=getpar('eitem');   
    $sql1="delete from $table_polozky where id=$eitem; ";
    $sql2="delete from $table_prava where id=$eitem and objekt='ITEM'; ";
    $bind=array();     
    $er=$this->db->Sql($sql1,$bind);
    if ($er){
      return $er;
    }else{
       $er=$this->db->Sql($sql2,$bind);
       if ($er){
         return $er; 
       }
    }
    return false;   
  }
  
  function deleteArticleAccessProp(){
    $table_prava=$this->table.'_prava';
    $eitem=getpar('eitem');
    $u=getpar('uu');
    $p=getpar('p');
    $g=getpar('g');
    if ($u==$this->user && $p=='OWN'){
      htpr(bt_dialog('Nelze odstranit','Toto oprávnění nemůžete odstranit samostatně. Smažte položku.'));
      return;
    }
    
    $sql="delete from $table_prava where id=:eitem and objekt='ITEM' ".
     "and trim(skupina)=:g and trim(uzivatel)=:u and privilege=:p ";
    if ($this->db->Sql($sql,array(':eitem'=>$eitem,':g'=>$g,':u'=>$u,':p'=>$p))){
       htpr(bt_dialog('Chyba','Data nebyla uložena.'));
    }else{
       //htpr(bt_dialog('Uloženo.','Data uložena.'));
    } 
  }
  
  function insertArticleAccessProp(){
    $table_prava=$this->table.'_prava';
    $eitem=getpar('eitem');
    $u=getpar('UZIVATEL');
    $p=getpar('PRIVILEGE');
    $g=getpar('SKUPINA');
    if ($u.$g=='' || $p=='') return; /* nothing to do whne empty */
    if ($u!='' && $g!='') $g='';     /* prefer user rights before group */
    if ($u=='') $u='NONE';
    if ($g=='') $g='NONE';
        
    $sql="insert into $table_prava (id,objekt,skupina,uzivatel,privilege,dbuser,dbdatum) ".
         "values (:eitem,'ITEM',:g, :u, :p, '".$this->user."',".$this->sysdate.")";
    if ($this->db->Sql($sql,array(':eitem'=>$eitem,':g'=>$g,':u'=>$u,':p'=>$p))){
       htpr(bt_dialog('Chyba','Data nebyla uložena.'));
    }else{
       //htpr(bt_dialog('Uloženo.','Data uložena.'));
    }
     
  }

  /** get info if can manage current item (folder, article)
   *  @param $item
   *  @param $type ('FOLDER','ITEM')
   */
  function canManage($item,$type='ITEM'){
    if ($item && $type) return true;
  }
  
  /** prototype for overrriding */
  function itemToPath($item){
    if ($item) return '';
  }
  
  function folderAccessProp($sitem){
    $table_prava=$this->table.'_prava';
    $table_user=$this->table.'_uziv';
    $table_groups=$this->table.'_skup';
    /*seznam opravneni na slozku, vcetne editacniho formulare*/
    $cache=
     ta('h3',http_lan_text('Access rights for the folder','Oprávnění přístupu ke složce')); 
    $SS=array('ALL'=>'Všichni');
    $this->db->Sql("select * from $table_groups");
    while ($this->db->FetchRow()){
      $SS[$this->db->Data('SKUPINA')]=$this->db->Data('NAZEV');
    }
    $SU=array();
    $this->db->Sql($this->concat=='||'?
     "select trim(ljmeno) as ljmeno,jmeno||' '||prijmeni as jm from $table_user":
     "select trim(ljmeno) as ljmeno, ".$this->concat."(jmeno,' ',prijmeni) as jm from $table_user");
    while ($this->db->FetchRow()){
      $SU[$this->db->Data('LJMENO')]=$this->db->Data('JM');
    } 
    $this->db->Sql("select * from $table_prava where id=$sitem and objekt='FOLDER'");
    $cache.='<table border="0" width="80%">';
    while ($this->db->FetchRow()){
      list($p1,$p2,$p3,$p0)=array(trim($this->db->Data('SKUPINA')),
                                  trim($this->db->Data('UZIVATEL')),
                                  $this->db->Data('PRIVILEGE'),
                                  $this->db->Data('OBJEKT'));
      if ($p1 == 'NONE'){
        $p4=$SU[$p2].tdtd().' [uživatel]';
      }
      if ($p2 == 'NONE'){
        $p4=$SS[$p1].tdtd().' [skupina]';
      }    
      $cache.=trtd().$p4.tdtd().$this->def_s_p[$p3].tdtd().
       ahref('?DA=1&amp;f=1&amp;g='.$p1.'&amp;uu='.$p2.'&amp;p='.
        $p3.'&amp;o='.$p0.'&amp;eD=1'.'&amp;item='.getpar('item'),
        http_lan_text('Delete access','Smazat oprávnění'),'class="btn btn-outline-primary"').trow();
    }
    $cache.='</table>';
    $cache.=tg('form','action="'.$_SERVER['SCRIPT_NAME'].'"',
     tg('table', 'width="100%"',
     trtd(2).
     para('item',getpar('item')).para('eD','1').para('f','1').
     lov(
       http_lan_text('User','Uživatel'),
       'UZIVATEL',
       $this->db,
       ($this->concat=='||'?
       "select ljmeno,jmeno||' '||prijmeni as jm from $table_user":
       "select ljmeno,".$this->concat."(jmeno,' ',prijmeni) as jm from $table_user")
     ).
     ' nebo '.
     lov(
       http_lan_text('group','skupina'),
       'SKUPINA',
       $this->db,
       "select distinct skupina, nazev from $table_groups"
     ).tdtd(). 
     lov(http_lan_text('Permission','Oprávnění'),'PRIVILEGE','',$this->dv,'VIEW').
     tdtd().
     submit('IA',http_lan_text('Add Permission','Přidat oprávnění'),'btn btn-outline-primary').
     trow().
    trtd(4).
     http_lan_text(
     '(Permission is set for a group or for an user. Choose the group or the user'.
     ' When a group is chosen, the user is irrelevant.)',
     '(Oprávnění se definuje pro skupinu nebo uživatele. Zapište buď uživatele'.
     ' a nebo vyberte skupinu. Při výběru skupiny se uživatel ignoruje.)').trow()));
    return tg('div',' ',$cache); 
  }

  function articleAccessProp($sitem,$typ){
    $table_prava=$this->table.'_prava';
    $table_user=$this->table.'_uziv';
    $table_groups=$this->table.'_skup';

    $cache=
     ta('h3',http_lan_text('Access rights for the item','Oprávnění přístupu k položce')); 
    $SS=array();
    $this->db->Sql("select skupina, nazev from $table_groups");
    while ($this->db->FetchRow()){
      $SS[$this->db->Data('SKUPINA')]=$this->db->Data('NAZEV');
    }
    $SU=array();
    $this->db->Sql($this->concat=='||'?
     "select trim(ljmeno) as ljmeno,jmeno||' '||prijmeni as jm from $table_user":
     "select trim(ljmeno) as ljmeno, ".$this->concat."(jmeno,' ',prijmeni) as jm from $table_user");
    while ($this->db->FetchRow()){
      $SU[trim($this->db->Data('LJMENO'))]=$this->db->Data('JM');
    } 
    $this->db->Sql(
      "select * ".
      "from $table_prava ".
      "where id=:sitem and objekt='ITEM'",
      array(':sitem'=>$sitem));
    $cache.='<table border="0" width="80%">';
    while ($this->db->FetchRow()){
      list($p1,$p2,$p3,$p0)=
       array(trim($this->db->Data('SKUPINA')),
             trim($this->db->Data('UZIVATEL')),
             $this->db->Data('PRIVILEGE'),
             $this->db->Data('OBJEKT'));
      if ($p1 == 'NONE'){
        $p4=(isset($SU[$p2])?$SU[$p2]:$p2).tdtd().' [uživatel]';
      }
      if ($p2 == 'NONE'){
        $p4=(isset($SS[$p1])?$SS[$p1]:$p1).tdtd().' [skupina]';
      }    
      $cache.=trtd().$p4.tdtd().$this->def_s_p[$p3].tdtd().
       ahref('?DA=1&amp;i=1&amp;type='.$typ.'&eitem='.$sitem.'&amp;g='.$p1.'&amp;uu='.$p2.'&amp;p='.
        $p3.'&amp;o='.$p0.'&amp;eD=1'.'&amp;item='.getpar('item'),
        http_lan_text('Delete access','Smazat oprávnění'),'class="btn btn-outline-primary"').trow();
    }
    $cache.=tg('form','action="'.$_SERVER['SCRIPT_NAME'].'"',
     para('item',getpar('item')).
     para('eD','1').
     para('i','1').
     para('eitem',$sitem).
     para('type',$typ).
     lov(trtd(2).
       http_lan_text('User','Uživatel'),
       'UZIVATEL',
       $this->db,
       ($this->concat=='||'?
       "select ljmeno,jmeno||' '||prijmeni as jm from $table_user":
       "select ljmeno, ".$this->concat."(jmeno,' ',prijmeni) as jm from $table_user")
     ).
     ' nebo '.
     lov(
       http_lan_text('group','skupina'),
       'SKUPINA',
       $this->db,
       "select distinct skupina, nazev from $table_groups"
     ).tdtd(). 
     lov(http_lan_text('Permission','Oprávnění'),'PRIVILEGE','',$this->dv,'VIEW').
     tdtd().
     submit('IA',http_lan_text('Add Permission','Přidat oprávnění'),'btn btn-outline-primary')
    ).trow().
    trtd(4).
     http_lan_text(
     '(Permission is set for a group or for an user. Choose the group or the user'.
     ' When a group is chosen, the user is irrelevant.)',
     '(Oprávnění se definuje pro skupinu nebo uživatele. Zapište buď uživatele'.
     ' a nebo vyberte skupinu. Při výběru skupiny se uživatel ignoruje.)'.trow().'</table>'
     );
    return tg('div',' ',$cache); 
  }
  
  function check($form){
    $err='';
    if ($form=='article'){
      $por=getpar('PORADI');
      if (!is_numeric($por)){
        $err.=http_lan_text('Order must be an integer value','Pořadí musí být celé číslo.');      
      }    
    }
    if ($form=='folder'){
      $por=getpar('PORADI');
      if (!is_numeric($por)){
        $err.=http_lan_text('Order must be an integer value. ','Pořadí musí být celé číslo.');      
      }
      /*if (trim(getpar('NAZEV'))==''){
        $err.=http_lan_text('Name is compulsory item. ','Název je třeba vyplnit. ');
      } */
      if (trim(getpar('ZKR_NAZEV'))==''){
        $err.=http_lan_text('Short name is compulsory item. ','Zkrácený název je třeba vyplnit. ');
      }    
    }  
    return $err;
  }

  /** Store an application log message into the database
   *  @param $message string 
   *  */ 
  function log_mess($message){
     $this->db->Sql("insert into ".$this->table."_log_tab (datum,text) values ".
                   "(".$this->sysdate.",'".$this->user.': '.$message."')");                  
  }
  
  /** Get the current user
   *  @return login name of the current user
   */ 
  function get_user(){
    return $this->user;
  }

  /** Get the list of all groups where is the current user
   *  @return array list of groups IDs
   */
    
  function get_groups(){
    $table_uskup=$this->table.'_uskup';
    return to_array("select skupina from $table_uskup where uzivatel=:uzivatel",
                    $this->db,
                    array(':uzivatel'=>$this->user));
  }

  /** Check whether the current user is in the given group
   *  @param $group
   *  @return boolean true or false
   */
    
  function is_in_group($group){
    $table_uskup=$this->table.'_uskup';
    $this->db->Sql(
     "select skupina ".
     "from $table_uskup ".
     "where uzivatel=:uzivatel and skupina=:skupina",
     array(':uzivatel'=>$this->user,':skupina'=>$group));
    if ($this->db->FetchRow()){
      return true;
    }
    return false;  
  }

  /** interface to database stored login-user parametres
   *  @param string $key the parametr
   *  @return string a value of the $key parametr, empty string if missing
   */
  function get_user_setting($key){
    $value=$this->db->SqlFetch(
      "select hodnota ".
      "from ".$this->table."_unastav ".
      "where ljmeno=:ljmeno and param=:param",
      array(':ljmeno'=>$this->user,
            ':param'=> $key));
    return $value;
  }
  
  /** check if the user-specific parametr was set in database
   *  @param string $key the parametr
   *  @return int 1 if the parametr is present in the database, 0 otherwise
   */
  function exists_user_setting($key){
    $count=$this->db->SqlFetch(
      "select count(hodnota) as pocet ".
      "from ".$this->table."_unastav ".
      "where ljmeno=:ljmeno and param=:param",
      array(':ljmeno'=>$this->user,
            ':param'=> $key));
    return (int)$count;
  }
   
  /** interface to database stored login-user parametres
   *  @param string $key the parametr
   *  @return string a value of the $key parametr, empty string if missing
   */
  function set_user_setting($key,$value){
    if ($this->exists_user_setting($key)){
       /* update */
       $e=$this->db->Sql(
         "update ".$this->table."_unastav ".
         "set hodnota=:hodnota ".
         "where ljmeno=:ljmeno and param=:param",
         array(':ljmeno'=>$this->user,
               ':param'=> $key,
               ':hodnota'=>$value));
    }else{
       /* insert */
       $e=$this->db->Sql(
        "insert into  ".$this->table."_unastav ".
        "(ljmeno, param, hodnota) values (:ljmeno, :param, :hodnota) ",
        array(':ljmeno'=>$this->user,
              ':param'=> $key,
              ':hodnota'=>$value));
    }
    if ($e) {
        htpr(bt_alert('Parametr '.$key.' se nenastavil na hodnotu '.$value),'alert-danger');  
    }
  }
}

?>