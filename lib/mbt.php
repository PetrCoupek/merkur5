<?php
/** lib_bt.php  - MicroBe core library
 * 
 * Framework library for simplified use the selected Boostrap components 
 *  
 * @author Petr Čoupek
 * @package Merkur5
 * @version 1.3
 * date 03.08.2023 18.08.2023 28.11.2023
 * 08.01.2024 - bt_pagination robusteness
 * 11.01.2024 - english with fnc la() for bt_pagination
 */

/** The function returns HTML tag for date input based on Bootstrap datefield plug-in functionality
 * @param string $label - label before the tag
 * @param string $name - the name of the input tag (name parameter in the form and also the id in the document)
 * @param string $value - initial date in the text input
 * @param string $add - other added parametres in the tag (useful for javascript client-side functionality or styling)
 * @param integer $f - form name, in which the tag is placed
 * @return HTML string */
 
function bt_datefield($label,$name,$value,$add='',$f=''){
  $id=$name.'_'.$f;
  $options='language: "cs",'.
           'clearBtn: true,'.
           'todayBtn: "linked",'.
           'daysOfWeekHighlighted: "0",'.
           'autoclose: true,'.
           'todayHighlight: true';
  $path=M5::get('path_relative');
  M5::puthf(
            tg('script','src="'.$path.'/vendor/datepicker/js/bootstrap-datepicker.js"',' ').
             tg('script','src="'.$path.'/vendor/datepicker/js/locales/bootstrap-datepicker.cs.js"',' ').
             tg('link','rel="stylesheet" media="screen,print" href="'.$path.'/vendor/datepicker/css/bootstrap-datepicker3.css" type="text/css" ','noslash')."\n",
            'datepicker');
  return tg('script','type="text/javascript"',
          ' $( function() {'.
          '   $( "#'.$id.'" ).datepicker({'.$options.'});'.
          '  } );').
        $label.($label!=''?nbsp(1):'').
        tg('input','type="text" name="'.$name.'" id="'.$id.'" value="'.$value.'" size="10" '.$add);
}

/** The function provides a page splitter / divider into sections based on the bootstrap css library
 * @param array $content - all the parts in form 'head', 'content' - internal HTML code
 * @param string $id - the document id 
 * @return HTML string */

function bt_accordion($content,$id='accordion',$aria_exp=1){
  $t=''; $i=0; 
  foreach ($content as $item){
    $i++;
    $t.="\n".tg('div','class="card"',
         tg('div','class="card-header" id="h'.($i).'"',
           //tg('h5','class="mb-0"',
             tg('button','class="btn btn-link btn-block text-left" data-toggle="collapse" '.
              'data-target="#collapse'.($i).'" aria-expanded="'.
              ($i==$aria_exp?'true':'false').'" aria-controls="collapse'.($i).'"',
              $item['header'].' ')).
         "\n".tg('div','id="collapse'.($i).'" class="collapse'.($i==$aria_exp?' show':'').'" aria-labelledby="h'.$i.
            '" data-parent="#'.$id.'" ',
          tg('div','class="card-body"',$item['content'])));   
  }
  return "\n".tg('div',' id="'.$id.'"',$t)."\n";
}


/** The function returns an input HTML field with the autocomplete functionality based on Autocomplete plugin for Bootstrap 
 * see https://docs-test-2.readthedocs.io/en/latest/
 * @param string  $label - label before the input
 * @param string  $name - the identifier for the input area
 * @param integer $size - the size in chars
 * @param string  $value - default value (f.e. for the database)
 * @param string  $placeholder - the initial hint at the text field position vanishing by typing
 * @return HTML string 
 */
function bt_autocomplete($label,$name,$url,$value='',$add='',$placeholder=''){
  $path=M5::get('path_relative');
  if ($placeholder=='') $placeholder=(isset($_SESSION['la']) && $_SESSION['la']=='en')?'enter text...':'zadejte text...';
  M5::puthf('<script src="'.$path.'/vendor/autocomplete/bootstrap-autocomplete.min.js"></script>'."\n",
  'autocomplete');
  $r=ta('span',$label).
     tg('select','class="form-control basicAutoSelect'.$name.'" name="'.$name.'" id="'.$name.'" placeholder="'.$placeholder.'" ',' ');
  $r.=ta('script',"$('.basicAutoSelect$name').autoComplete({
    resolverSettings: {
      url: '".$url."',     
      autocomplete: 'off',
      noResultsText: 'Nic nenalezeno.' },
      minLength: 1".($add!=''?(",\n".$add):'')."});");    
  if ($value!=''){
    /* set the appropriate value and 
       find also the text written on the screen */
    $r.=ta('script', 
    "console.log('$value') ;\n".
    '$.ajax({url:"'.$url.'?id='.$value.'",'.
            'success: function(result){ '.
            'console.log(result);'.
            "$('.basicAutoSelect$name').autoComplete('set', { value: '".$value."', text: result['text'] });".
            '}});');
    //"$('.basicAutoSelect$name').autoComplete('set', { value: '".$value."', text: '".($value.'aaa')."' });");
  }
  return $r;
}

/** The function provides an Info dialog over HTML screen using Bootstrap Modal dialog. There is OK button to close it.
 * @param string $title - dialog title
 * @param string $body - dialog content 
 * @return HTML string
 */ 
function bt_dialog($title,$body){
 return (tg('script','type="text/javascript"',
  '$(window).on(\'load\',function(){ $(\'#bt_dialog\').modal(\'show\'); });').
  tg('div','class="modal" id="bt_dialog" tabindex="-1" role="dialog"',
   tg('div', 'class="modal-dialog modal-dialog-centered" role="document"',
    tg('div','class="modal-content"',
     tg('div','class="modal-header"',
      tg('h5','class="modal-title"',$title).
       tg('button','type="button" class="close" data-dismiss="modal" aria-label="Close"',
        tg('span','aria-hidden="true"','&times;'))).
     tg('div','class="modal-body"',tg('p','style="word-wrap:break-word;"',$body)).
     tg('div','class="modal-footer"',
      tg('button','type="button" class="btn btn-primary" data-dismiss="modal"','OK')))))
  );
}

/** The function provides an Alert dialog over HTML screen using Bootstrap Modal dialog. There is OK button to close it.
 * @param string $body - dialog content
 * @param string $type - Alert type in the Bootstrap. Default 'alert-success'.
 * @return HTML string
 */ 

function bt_alert($body,$type='alert-success'){
  return tg('div','class="alert '.$type.'" role="alert"',
          $body.
          tg('button','type="button" class="close" data-dismiss="alert" aria-label="Close"',
           tg('span','aria-hidden="true"','&times;')));
} 

/** The function provides a Dialog over HTML screen using Bootstrap Modal dialog. There is OK button to close it.
 * @param string $title - dialog title
 * @param string $body - dialog content
 * @param string commit - commit action for post request
 * @return HTML string
 */
  
function bt_fdialog($title,$body,$commit=''){
 if ($commit==''){
   $commit=tg('input','type="submit" class="btn btn-primary" value="OK" ','noslash');
 } 
 return (tg('script','type="text/javascript"',
  '$(window).on(\'load\',function(){ $(\'#bt_dialog\').modal(\'show\'); });').
  tg('div','class="modal" id="bt_dialog" tabindex="-1" role="dialog"',
   tg('div', 'class="modal-dialog modal-dialog-centered" role="document"',
    tg('div','class="modal-content"',
    tg('form','method="post" action="'.$_SERVER['SCRIPT_NAME'].'"',
     tg('div','class="modal-header"',
      tg('h5','class="modal-title"',$title).
       tg('button','type="button" class="close" data-dismiss="modal" aria-label="Close"',
        tg('span','aria-hidden="true"','&times;'))).
     tg('div','class="modal-body"',tg('p','style="word-wrap:break-word;"',$body)).
     tg('div','class="modal-footer"',
      tg('button','type="button" class="btn btn-secondary" data-dismiss="modal"',
        http_lan_text('Cancel','Storno')).
      $commit ))))));
 }


/** Switch-able tab panels with cards on one page.
 * @param array $sections - list of headers of sections
 * @param array $contents - list of contents for sections
 * @return Bootstrap HTML content
 */ 

function bt_panels($sections,$contents){
 
  $sh='';
  foreach ($sections as $k=>$v){
    /* $link='?item='.getpar('item').'&amp;KLIC_GDO='.getpar('KLIC_GDO').
          '&amp;tt_=d&amp;WHR_='.urlencode(getpar('WHR_')).
          '&amp;CUR_='.getpar('CUR_').'&amp;sekce='.$k.''; */
    if (strlen($contents[$k])<50) continue;    /* pokud jsou obsahy prazdny retezec, nezaradi ani tu danou sekci */   
    $sh.=tg('li','class="nav-item" role="presentation"',"\n".
           tg('a','class="nav-link'.($k==1?' active':'').'" id="tab'.$k.'" data-toggle="tab" href="#ob'.$k.
                '" role="tab" aria-controls="ob'.$k.'" aria-selected="'.($k==1?'true':'false').'"',
                $v));
  }               
  $sh=tg('div','class="card-header"',"\n".
      tg('ul','class="nav nav-tabs card-header-tabs" id="ouska" role="tablist"',$sh));
  
  $so='';
  foreach ($contents as $k=>$v){
   if (strlen($v)<50) continue;     /* pokud jsou obsahy prazdny retezec, nezaradi ani tu danou sekci */
    $so.=tg('div','class="tab-pane fade '.($k==1?'show active':'').'" id="ob'.
              $k.'" role="tabpanel" aria-labelledby="tab'.$k.'"',
              $v)."\n";
  }
  $so=tg('div','class="tab-content" id="ouskaContent"',"\n".$so);     
  return $sh.$so;
}

/** Upper bar menu for application template
 * @param string $title an appliaction title
 * @param array $leftMenu an array with definiton upper bar menu. It conatins memu labels and actions,
 * @param array $rightSide -HTML text with possible actions ()
 *  or first level submenus with defined heading label - 'header' in the array
 * Example left menu: array(
        'Home'=>'?',
        'Option1'=>'?item=1',
        'Other actions'=>array(
                    'header'=>'Other actions ***',
                    'Akce1'=>'?item=3',
                    'Akce2'=>'?item=4')
 * 
 */

function bt_menu($title,$leftMenu=array(),$rightSide=''){
  $s='';$n=0;
  foreach ($leftMenu as $k=>$v){
    if (is_array($v)){
      /* nove dropdown menu */
      $s1='';
      foreach ($v as $kk=>$vv){
        $s1.=tg('a','class="dropdown-item"  href="'.$vv.'"',$kk);
      }
      $s.=
       tg('li','class="nav-item dropdown"',
        tg('a','class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink'.(++$n).
          '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"',
         $k).
         tg('div','class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink'.$n.'"',$s1));
    }else{
      /* polozka menu */
      $s.=tg('li','class="nav-item"',
        tg('a','class="nav-link" href="'.$v.'"',$k));

    }
  }
  $s=tg('div','class="navbar-collapse collapse w-100 order-1 order-md-0 dual-collapse2"',
      tg('ul','class="navbar-nav mr-auto"',$s));
  $s.=tg('div','class="mx-auto order-0"',
       tg('button','class="navbar-toggler" type="button" data-toggle="collapse" data-target=".dual-collapse2" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation"',
        tg('span','class="navbar-toggler-icon"','',true)).
       tg('a','class="navbar-brand mx-auto" href="?"',$title) 
  );
  $s.=tg('div','class="navbar-collapse collapse w-100 order-3 dual-collapse2"',
      tg('div','class="float-right ml-auto"',$rightSide));
  $s=tg('nav','class="navbar navbar-expand-md navbar-dark bg-dark fixed-top"',$s);
  return $s;
}


/**
 * Bootstrap container helper for table based on CSS styles
 * @param array $colrules - rules for columens in bt-styles
 * @param array $rows  - array with container's rows in table
 * @param string $rowclass - the class used on every row, default: row
 * example : bt_container(
          ['col-1','col-3','col-8'],
          [['row1 col1 text', 'row1 col3 text', 'row1 col8 text'],
           ['row2 col1 text', 'row2 col3 text', 'row2 col8 text'],
           ['row3 col1 text', 'row3 col3 text', 'row3 col8 text']]);
 */
function bt_container($colrules,$rows,$rowclass='row'){
  $r='';
  if (!is_array($colrules)||count($colrules)<1) return '';
  if (!is_array($rows)||count($rows)<1) return '';
  for($i=0;$i<count($rows);$i++){
    $t='';
    for($j=0;$j<count($rows[$i]);$j++){
      /* osetreni chybne vytvoreneho pole */
      if (isset($rows[$i][$j])) $t.=tg('div','class="'.$colrules[$j].'"',$rows[$i][$j].' '); 
      /* mezera na konci pro zamezeni degenerace prazdnych tagu */
    }
    $r.=tg('div','class="'.$rowclass.'"',$t.' '); /*mezera*/  
  }
  $r=tg('div','class="container"',$r.' '); /*mezera*/
  return $r;
}


/** The bt_hideable area on the page - Bootstrap container helper
 * @param string $label - always visible and clicable label
 * @param string $docid - unique document ID }for javascript functionality)
 * @param string $content - internal HMTL content
 * @param string $addlabel additional parameters in the tabel tag, f.e class="text-decoration-none" class="btn: etc.
 * @param bool   $hide - if it is hidden at the start
 */

function bt_hidable_area($label, $docid, $content, $addlabel='', $hide=true){
  return 
   tg('div','id="'.$docid.'_o" ',
   ta('h5',tg('a','id="'.$docid.'_c" href="#" '.$addlabel, $label.nbsp(2).bt_icon('caret-down'))).
  
   tg('div','id="'.$docid.'_i" '.($hide?'style="display:none"':''), $content.
     tg('a','id="'.$docid.'_d" href="#" ',bt_icon('caret-up')))).
   ta('script',
    '$(document).ready(function(){'.
      '$("#'.$docid.'_c").click(function(){'.
        '$("#'.$docid.'_i").toggle(); });  '.
      //' /* $("#'.$docid.'_i").hide(); */ '.
      '$("#'.$docid.'_d").click(function(){'.
        '$("#'.$docid.'_i").hide(); });  '. 
     '});');
 }


/** The SVG icon library 
 * @param string $name - icon idenfier ('arrow-right','caret-up' etc.)
 * @param string $add - added directiove in svg, for exaple fill="#000000" to force black icon
*/
function bt_icon($name='info-square',$add=''){
  //$p1='xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi '.$name.'" viewBox="0 0 16 16" ';
  //$p2='version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="16" height="16" fill="currentColor" class="bi '.$name.'" viewBox="0 0 16 16"';
  $p1='xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="bi '.$name.'" viewBox="0 0 16 16" fill="currentColor"';
  $p2='version="1.1" xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="bi '.$name.'" viewBox="0 0 16 16" fill="currentColor"';
  
  switch ($name){
  case 'chevron-down':
  case 'on':  
    return tg('svg',$p1,
     tg('path','fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"'.$add));
  case 'chevron-left':
    return tg('svg',$p1,
     tg('path','fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"'.$add));
  case 'chevron-right':
     return tg('svg',$p1,
     tg('path','fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"'.$add));
  case 'chevron-up':
  case 'off':   
    return tg('svg',$p1,
     tg('path','fill-rule="evenodd" d="M7.646 4.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1-.708.708L8 5.707l-5.646 5.647a.5.5 0 0 1-.708-.708l6-6z"'.$add));
  case 'arrow-left':
    return tg('svg',$p1,
     tg('path','fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"'.$add));
  case 'arrow-right':
    return tg('svg',$p1,
     tg('path','fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"'.$add));    
  case 'caret-down':
    return tg('svg',$p1,
     tg('path','d="M3.204 5h9.592L8 10.481 3.204 5zm-.753.659 4.796 5.48a1 1 0 0 0 1.506 0l4.796-5.48c.566-.647.106-1.659-.753-1.659H3.204a1 1 0 0 0-.753 1.659z"'.$add));   
  case 'caret-up':
    return tg('svg',$p1,
     tg('path','d="M3.204 11h9.592L8 5.519 3.204 11zm-.753-.659 4.796-5.48a1 1 0 0 1 1.506 0l4.796 5.48c.566.647.106 1.659-.753 1.659H3.204a1 1 0 0 1-.753-1.659z"'.$add));  
  case 'check':
    return tg('svg',$p1,
     tg('path','d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"'.$add));
  case 'check-circle':
    return tg('svg',$p1,
     tg('path','d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"'.$add).
     tg('path','d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z"'.$add));
  case 'geo-alt':
  case 'location':  
    return tg('svg',$p1,
     tg('path','d="M12.166 8.94c-.524 1.062-1.234 2.12-1.96 3.07A31.493 31.493 0 0 1 8 14.58a31.481 31.481 0 0 1-2.206-2.57c-.726-.95-1.436-2.008-1.96-3.07C3.304 '.
               '7.867 3 6.862 3 6a5 5 0 0 1 10 0c0 .862-.305 1.867-.834 2.94zM8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10z"'.$add).
     tg('path','d="M8 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm0 1a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"'.$add));     
  case 'menu-app':
    return tg('svg',$p1,
     tg('path','d="M0 1.5A1.5 1.5 0 0 1 1.5 0h2A1.5 1.5 0 0 1 5 1.5v2A1.5 1.5 0 0 1 3.5 5h-2A1.5 1.5 0 0 1 0 3.5v-2zM1.5 1a.5.5 0 0 0-.5.5v2a.5.5 0 0 0 .5.5h2a.5.5 0 0 '.
                '0 .5-.5v-2a.5.5 0 0 0-.5-.5h-2zM0 8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V8zm1 3v2a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2H1zm14-1V8a1 1 0 0 '.
                '0-1-1H2a1 1 0 0 0-1 1v2h14zM2 8.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zm0 4a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1h-6a.5.5 0 0 1-.5-.5z"'.$add));
  case 'power':
    return tg('svg',$p1,
     tg('path','d="M7.5 1v7h1V1h-1z"'.$add).
     tg('path','d="M3 8.812a4.999 4.999 0 0 1 2.578-4.375l-.485-.874A6 6 0 1 0 11 3.616l-.501.865A5 5 0 1 1 3 8.812z"'.$add));
  case 'plusminus':
    return tg('svg',$p1,
     tg('path','d="m1.854 14.854 13-13a.5.5 0 0 0-.708-.708l-13 13a.5.5 0 0 0 .708.708ZM4 1a.5.5 0 0 1 .5.5v2h2a.5.5 0 0 1 0 1h-2v2a.5.5 0 0 1-1 0v-2h-2a.5.5 0 0 '.
                '1 0-1h2v-2A.5.5 0 0 1 4 1Zm5 11a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5A.5.5 0 0 1 9 12Z"'.$add));
  case 'exclamation-triangle':
    return tg('svg',$p1,
     tg('path','d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.146.146 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.163.163 0 '.
     '0 1-.054.06.116.116 0 0 1-.066.017H1.146a.115.115 0 0 1-.066-.017.163.163 0 0 1-.054-.06.176.176 0 0 1 .002-.183L7.884 2.073a.147.147 0 0 '.
     '1 .054-.057zm1.044-.45a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566z"'.$add).
     tg('path','d="M7.002 12a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 5.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995z"'.$add));
  case 'check-square':
    return tg('svg',$p1,
     tg('path','d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"'.$add).
     tg('path','d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z"'.$add));
  case 'gem':
  case 'diamond':
    return tg('svg',$p1,
     tg('path','d="M3.1.7a.5.5 0 0 1 .4-.2h9a.5.5 0 0 1 .4.2l2.976 3.974c.149.185.156.45.01.644L8.4 15.3a.5.5 0 0 1-.8 0L.1 5.3a.5.5 0 0 1 0-.6l3-4zm11.386 3.785-1.806-2.41-.776 '.
     '2.413 2.582-.003zm-3.633.004.961-2.989H4.186l.963 2.995 5.704-.006zM5.47 5.495 8 13.366l2.532-7.876-5.062.005zm-1.371-.999-.78-2.422-1.818 2.425 '.
     '2.598-.003zM1.499 5.5l5.113 6.817-2.192-6.82L1.5 5.5zm7.889 6.817 5.123-6.83-2.928.002-2.195 6.828z"'.$add));  
  case 'dot':
    return tg('svg',$p1,
     tg('path','d="M8 9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z"'.$add));
  case 'lock':   
  case 'lock-fill':
    return tg('svg',$p1,
     tg('path','d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"'.$add));
  case 'question':
    return tg('svg',$p1,
     tg('path','d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"'.$add).
     tg('path','d="M5.255 5.786a.237.237 0 0 0 .241.247h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 '.
     '1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 '.
     '0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286zm1.557 5.763c0 .533.425.927 1.01.927.609 0 1.028-.394 1.028-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94z"'.$add));      
  case 'list':
  case 'menu':
  case 'hamburger-menu':
    return tg('svg',$p1,
     tg('path','fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"'.$add));      
  case 'geolocation':
    return tg('svg',$p1,
     tg('path','d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"'.$add));   
   
  /* Moon icons, https://icomoon.io/#preview-free */
  case 'floppy':  
  case 'floppy-disc':
    return tg('svg','version="1.1" xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="bi floppy-disc" viewBox="0 0 16 16" fill="currentColor"',
     tg('path','d="M14 0h-14v16h16v-14l-2-2zM8 2h2v4h-2v-4zM14 14h-12v-12h1v5h9v-5h1.172l0.828 0.828v11.172z"'.$add));
  case 'save':  
    return tg('svg',$p2,
     tg('path','d="M14 0h-14v16h16v-14l-2-2zM8 2h2v4h-2v-4zM14 14h-12v-12h1v5h9v-5h1.172l0.828 0.828v11.172z"'.$add));
  case 'floppy-add':
      return  tg('svg',$p2,
       tg('path',
       'd="M11.5 7c-2.485 0-4.5 2.015-4.5 4.5s2.015 4.5 4.5 4.5c2.485 0 4.5-2.015 4.5-4.5s-2.015-4.5-4.5-4.5zM14 12h-2v2h-1v-2h-2v-1h2v-2h1v2h2v1z"'.$add).
       tg('path','d="M14 0h-14v16h16v-14l-2-2zM8 2h2v4h-2v-4zM14 14h-12v-12h1v5h9v-5h1.172l0.828 0.828v11.172z"'.$add));
     
  case 'left':
    return tg('svg',$p2,tg('path','d="M0.5 8l7.5 7.5v-4.5h8v-6h-8v-4.5z"'.$add));
  case 'right':
    return tg('svg',$p2,tg('path','d="M15.5 8l-7.5-7.5v4.5h-8v6h8v4.5z"'.$add));
  case 'up':
    return tg('svg',$p2,tg('path','d="M8 0.5l-7.5 7.5h4.5v8h6v-8h4.5z"'.$add));
  case 'down':
    return tg('svg',$p2,tg('path','d="M8 15.5l7.5-7.5h-4.5v-8h-6v8h-4.5z"'.$add));
  case 'uptop':
    return tg('svg',$p2,tg('path','d="M0 0h16v3h-16v-3zM0 "'.$add).
           tg('path','d="M8 0.5l-7.5 7.5h4.5v8h6v-8h4.5z"'.$add));
  case 'downbottom':
    return tg('svg',$p2,tg('path','d="M0 13h16v3h-16v-3zM0 "'.$add).
           tg('path','d="M8 15.5l7.5-7.5h-4.5v-8h-6v8h-4.5z"'.$add));            
  case 'home':
    return tg('svg',$p2,tg('path','d="M16 9.5l-3-3v-4.5h-2v2.5l-3-3-8 8v0.5h2v5h5v-3h2v3h5v-5h2z"'.$add));
  case 'file-pdf':
    return tg('svg',$p2,
    tg('path','d="M13.156 9.211c-0.213-0.21-0.686-0.321-1.406-0.331-0.487-0.005-1.073 0.038-1.69 0.124-0.276-0.159-0.561-0.333-0.784-0.542-0.601-0.561-1.103-1.34-1.415-2.197 '.
    '0.020-0.080 0.038-0.15 0.054-0.222 0 0 0.339-1.923 0.249-2.573-0.012-0.089-0.020-0.115-0.044-0.184l-0.029-0.076c-0.092-0.212-0.273-0.437-0.556-0.425l-0.171-0.005c-0.316 '.
    '0-0.573 0.161-0.64 0.403-0.205 0.757 0.007 1.889 0.39 3.355l-0.098 0.239c-0.275 0.67-0.619 1.345-0.923 1.94l-0.040 0.077c-0.32 0.626-0.61 1.157-0.873 1.607l-0.271 '.
    '0.144c-0.020 0.010-0.485 0.257-0.594 0.323-0.926 0.553-1.539 1.18-1.641 1.678-0.032 0.159-0.008 0.362 0.156 0.456l0.263 0.132c0.114 0.057 0.234 0.086 0.357 0.086 '.
    '0.659 0 1.425-0.821 2.48-2.662 1.218-0.396 2.604-0.726 3.819-0.908 0.926 0.521 2.065 0.883 2.783 0.883 0.128 0 0.238-0.012 0.327-0.036 0.138-0.037 0.254-0.115 '.
    '0.325-0.222 0.139-0.21 0.168-0.499 0.13-0.795-0.011-0.088-0.081-0.196-0.157-0.271zM3.307 12.72c0.12-0.329 0.596-0.979 1.3-1.556 0.044-0.036 0.153-0.138 '.
    '0.253-0.233-0.736 1.174-1.229 1.642-1.553 1.788zM7.476 3.12c0.212 0 0.333 0.534 0.343 1.035s-0.107 0.853-0.252 1.113c-0.12-0.385-0.179-0.992-0.179-1.389 0 '.
    '0-0.009-0.759 0.088-0.759v0zM6.232 9.961c0.148-0.264 0.301-0.543 0.458-0.839 0.383-0.724 0.624-1.29 0.804-1.755 0.358 0.651 0.804 1.205 1.328 1.649 0.065 0.055 '.
    '0.135 0.111 0.207 0.166-1.066 0.211-1.987 0.467-2.798 0.779v0zM12.952 9.901c-0.065 0.041-0.251 0.064-0.37 0.064-0.386 0-0.864-0.176-1.533-0.464 0.257-0.019 '.
    '0.493-0.029 0.705-0.029 0.387 0 0.502-0.002 0.88 0.095s0.383 0.293 0.318 0.333v0z"'.$add));
  case 'file-word': 
    return tg('svg',$p2,
    tg('path','d="M9.997 7.436h0.691l-0.797 3.534-1.036-4.969h-1.665l-1.205 4.969-0.903-4.969h-1.741l1.767 7.998h1.701l1.192-4.73 1.066 4.73h1.568l2.025-7.998h-2.663v1.435z"'.$add).
    tg('path','d="M14.341 3.579c-0.347-0.473-0.831-1.027-1.362-1.558s-1.085-1.015-1.558-1.362c-0.806-0.591-1.197-0.659-1.421-0.659h-7.75c-0.689 0-1.25 0.561-1.25 1.25v13.5c0 '.
    '0.689 0.561 1.25 1.25 1.25h11.5c0.689 0 1.25-0.561 1.25-1.25v-9.75c0-0.224-0.068-0.615-0.659-1.421v0zM12.271 2.729c0.48 0.48 0.856 0.912 1.134 1.271h-2.406v-2.405c0.359 '.
    '0.278 0.792 0.654 1.271 1.134v0zM14 14.75c0 0.136-0.114 0.25-0.25 0.25h-11.5c-0.135 0-0.25-0.114-0.25-0.25v-13.5c0-0.135 0.115-0.25 0.25-0.25 0 0 7.749-0 7.75 0v3.5c0 '.
    '0.276 0.224 0.5 0.5 0.5h3.5v9.75z"'.$add));
  case 'file-excel':
    return tg('svg',$p2,
    tg('path','d="M11.61 6h-2.114l-1.496 2.204-1.496-2.204h-2.114l2.534 3.788-2.859 4.212h3.935v-1.431h-0.784l0.784-1.172 1.741 2.603h2.194l-2.859-4.212 2.534-3.788z"'.$add).
    tg('path','d="M14.341 3.579c-0.347-0.473-0.831-1.027-1.362-1.558s-1.085-1.015-1.558-1.362c-0.806-0.591-1.197-0.659-1.421-0.659h-7.75c-0.689 0-1.25 0.561-1.25 '.
    '1.25v13.5c0 0.689 0.561 1.25 1.25 1.25h11.5c0.689 0 1.25-0.561 1.25-1.25v-9.75c0-0.224-0.068-0.615-0.659-1.421v0zM12.271 2.729c0.48 0.48 0.856 0.912 1.134 '.
    '1.271h-2.406v-2.405c0.359 0.278 0.792 0.654 1.271 1.134v0zM14 14.75c0 0.136-0.114 0.25-0.25 0.25h-11.5c-0.135 0-0.25-0.114-0.25-0.25v-13.5c0-0.135 0.115-0.25 '.
    '0.25-0.25 0 0 7.749-0 7.75 0v3.5c0 0.276 0.224 0.5 0.5 0.5h3.5v9.75z"'.$add));
   case 'file-text':
    return tg('svg',$p2,
    tg('path','d="M14.341 3.579c-0.347-0.473-0.831-1.027-1.362-1.558s-1.085-1.015-1.558-1.362c-0.806-0.591-1.197-0.659-1.421-0.659h-7.75c-0.689 0-1.25 0.561-1.25 '.
    '1.25v13.5c0 0.689 0.561 1.25 1.25 1.25h11.5c0.689 0 1.25-0.561 1.25-1.25v-9.75c0-0.224-0.068-0.615-0.659-1.421zM12.271 2.729c0.48 0.48 0.856 0.912 1.134 '.
    '1.271h-2.406v-2.405c0.359 0.278 0.792 0.654 1.271 1.134zM14 14.75c0 0.136-0.114 0.25-0.25 0.25h-11.5c-0.135 0-0.25-0.114-0.25-0.25v-13.5c0-0.135 0.115-0.25 '.
    '0.25-0.25 0 0 7.749-0 7.75 0v3.5c0 0.276 0.224 0.5 0.5 0.5h3.5v9.75z"'.$add).
    tg('path','d="M11.5 13h-7c-0.276 0-0.5-0.224-0.5-0.5s0.224-0.5 0.5-0.5h7c0.276 0 0.5 0.224 0.5 0.5s-0.224 0.5-0.5 0.5z"'.$add).
    tg('path','d="M11.5 11h-7c-0.276 0-0.5-0.224-0.5-0.5s0.224-0.5 0.5-0.5h7c0.276 0 0.5 0.224 0.5 0.5s-0.224 0.5-0.5 0.5z"'.$add).
    tg('path','d="M11.5 9h-7c-0.276 0-0.5-0.224-0.5-0.5s0.224-0.5 0.5-0.5h7c0.276 0 0.5 0.224 0.5 0.5s-0.224 0.5-0.5 0.5z"'.$add));
   case 'pencil':
   case 'edit':
    return tg('svg',$p2,
    tg('path','d="M6 10l2-1 7-7-1-1-7 7-1 2zM4.52 13.548c-0.494-1.043-1.026-1.574-2.069-2.069l1.548-4.262 2-1.217 6-6h-3l-6 6-3 10 10-3 6-6v-3l-6 6-1.217 2z"'.$add));   
   case 'cross':
   case 'delete': 
    return tg('svg',$p2,
    tg('path','d="M15.854 12.854c-0-0-0-0-0-0l-4.854-4.854 4.854-4.854c0-0 0-0 0-0 0.052-0.052 0.090-0.113 0.114-0.178 0.066-0.178 '.
     '0.028-0.386-0.114-0.529l-2.293-2.293c-0.143-0.143-0.351-0.181-0.529-0.114-0.065 0.024-0.126 0.062-0.178 0.114 0 0-0 0-0 '.
     '0l-4.854 4.854-4.854-4.854c-0-0-0-0-0-0-0.052-0.052-0.113-0.090-0.178-0.114-0.178-0.066-0.386-0.029-0.529 0.114l-2.293 '.
     '2.293c-0.143 0.143-0.181 0.351-0.114 0.529 0.024 0.065 0.062 0.126 0.114 0.178 0 0 0 0 0 0l4.854 4.854-4.854 4.854c-0 0-0 0-0 '.
     '0-0.052 0.052-0.090 0.113-0.114 0.178-0.066 0.178-0.029 0.386 0.114 0.529l2.293 2.293c0.143 0.143 0.351 0.181 0.529 0.114 0.065-0.024 '.
     '0.126-0.062 0.178-0.114 0-0 0-0 0-0l4.854-4.854 4.854 4.854c0 0 0 0 0 0 0.052 0.052 0.113 0.090 0.178 0.114 0.178 0.066 0.386 0.029 '.
     '0.529-0.114l2.293-2.293c0.143-0.143 0.181-0.351 0.114-0.529-0.024-0.065-0.062-0.126-0.114-0.178z"'.$add));
   case 'plus':
     return tg('svg',$p2,
     tg('path','d="M15.5 6h-5.5v-5.5c0-0.276-0.224-0.5-0.5-0.5h-3c-0.276 0-0.5 0.224-0.5 0.5v5.5h-5.5c-0.276 0-0.5 0.224-0.5 '.
      '0.5v3c0 0.276 0.224 0.5 0.5 0.5h5.5v5.5c0 0.276 0.224 0.5 0.5 0.5h3c0.276 0 0.5-0.224 0.5-0.5v-5.5h5.5c0.276 0 '.
      '0.5-0.224 0.5-0.5v-3c0-0.276-0.224-0.5-0.5-0.5z"'.$add));
  case 'search':
     return tg('svg',$p2,
     tg('path','d="M15.504 13.616l-3.79-3.223c-0.392-0.353-0.811-0.514-1.149-0.499 0.895-1.048 1.435-2.407 1.435-3.893 '.
      '0-3.314-2.686-6-6-6s-6 2.686-6 6 2.686 6 6 6c1.486 0 2.845-0.54 3.893-1.435-0.016 0.338 0.146 0.757 0.499 1.149l3.223 3.79c0.552 '.
      '0.613 1.453 0.665 2.003 0.115s0.498-1.452-0.115-2.003zM6 10c-2.209 0-4-1.791-4-4s1.791-4 4-4 4 1.791 4 4-1.791 4-4 4z"'.$add));
  case 'camera':
  case 'photo':
     return tg('svg',$p2,
     tg('path','d="M4.75 9.5c0 1.795 1.455 3.25 3.25 3.25s3.25-1.455 3.25-3.25-1.455-3.25-3.25-3.25-3.25 1.455-3.25 3.25zM15 '.
      '4h-3.5c-0.25-1-0.5-2-1.5-2h-4c-1 0-1.25 1-1.5 2h-3.5c-0.55 0-1 0.45-1 1v9c0 0.55 0.45 1 1 1h14c0.55 0 1-0.45 1-1v-9c0-0.55-0.45-1-1-1zM8 '.
      '13.938c-2.451 0-4.438-1.987-4.438-4.438s1.987-4.438 4.438-4.438c2.451 0 4.438 1.987 4.438 4.438s-1.987 4.438-4.438 4.438zM15 7h-2v-1h2v1z"'.$add));
  case 'lab':
     return tg('svg',$p2,
     tg('path','d="M14.942 12.57l-4.942-8.235v-3.335h0.5c0.275 0 0.5-0.225 0.5-0.5s-0.225-0.5-0.5-0.5h-5c-0.275 0-0.5 '.
     '0.225-0.5 0.5s0.225 0.5 0.5 0.5h0.5v3.335l-4.942 8.235c-1.132 1.886-0.258 3.43 1.942 3.43h10c2.2 0 3.074-1.543 1.942-3.43zM3.766 '.
     '10l3.234-5.39v-3.61h2v3.61l3.234 5.39h-8.468z"'.$add));
  case 'compass':
     return tg('svg',$p2,
     tg('path','d="M8 0c-4.418 0-8 3.582-8 8s3.582 8 8 8 8-3.582 8-8-3.582-8-8-8zM1.5 8c0-3.59 2.91-6.5 6.5-6.5 1.712 '.
     '0 3.269 0.662 4.43 1.744l-6.43 2.756-2.756 6.43c-1.082-1.161-1.744-2.718-1.744-4.43zM9.143 9.143l-4.001 1.715 1.715-4.001 '.
     '2.286 2.286zM8 14.5c-1.712 0-3.269-0.662-4.43-1.744l6.43-2.756 2.756-6.43c1.082 1.161 1.744 2.718 1.744 4.43 0 3.59-2.91 6.5-6.5 6.5z"'.$add));    
  case 'droplet':
     return tg('svg',$p2,
     tg('path','d="M13.51 7.393c-1.027-2.866-3.205-5.44-5.51-7.393-2.305 1.953-4.482 4.527-5.51 7.393-0.635 1.772-0.698 '.
     '3.696 0.197 5.397 1.029 1.955 3.104 3.21 5.313 3.21s4.284-1.255 5.313-3.21c0.895-1.701 0.832-3.624 0.197-5.397zM11.543 11.859c-0.684 '.
     '1.301-2.075 2.141-3.543 2.141-0.861 0-1.696-0.29-2.377-0.791 0.207 0.027 0.416 0.041 0.627 0.041 1.835 0 3.573-1.050 4.428-2.676 '.
     '0.701-1.333 0.64-2.716 0.373-3.818 0.227 0.44 0.42 0.878 0.576 1.311 0.353 0.985 0.625 2.443-0.084 3.791z"'.$add));    
  case 'hammer':
  case 'geology':
     return tg('svg',$p2,
     tg('path','d="M15.781 12.953l-4.712-4.712c-0.292-0.292-0.769-0.292-1.061 0l-0.354 0.354-2.875-2.875 4.72-4.72h-5l-2.22 '.
     '2.22-0.22-0.22h-1.061v1.061l0.22 0.22-3.22 3.22 2.5 2.5 3.22-3.22 2.875 2.875-0.354 0.354c-0.292 0.292-0.292 0.769 '.
     '0 1.061l4.712 4.712c0.292 0.292 0.769 0.292 1.061 0l1.768-1.768c0.292-0.292 0.292-0.769 0-1.061z"'.$add));    
      
     
     
     
     
  /* default, when input doesnt match */
  default:
    return tg('svg',$p2,
    tg('path','d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 '.
     '2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"'.$add).
    tg('path','d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 '.
    '1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"'.$add));
  }

}

/** Tooltip - text displayed when mouse gooes over object
 * @param string $title - tooltip text
 * @param string $text - object - text normal visible 
 * @param string $placement - optional instruction on which side place the tooltip
 */
function bt_tooltip($title,$text,$placement='top'){

  return tg('span','data-toggle="tooltip" data-placement="'.$placement.'" title="'.$title.'"',$text);
}



/** Table list - a page with structured (database origin) table
 * @param string $caption - table caption
 * @param array $head - a hash keys are table columns an valueas are table header labels. When key begins
 *                      with _ , ti means tah that column } without that _) is not orderable 
 * @param array $content
 * @param string $nodata_text
 * @param string $bt_class
 * @param string $pagination (result of the bt_pagination function) 
 * @param string $content
 * @param bool  $postlink - when true, generater links are POSTed
 * @param string $filter - when not null, it prints this text as filter description
 * @param string $text_button Specify text for Search button
 */
function bt_lister($caption='',
                   $head=[],
                   $content=[[]],
                   $nodata_text='',
                   $bt_class='d-print-none',
                   $pagination='',
                   $context='',
                   $postlink=false,
                   $filter=null,
                   $text_button=''){

  $s=''; 
  if (!is_array($content)) return '';
  if ($bt_class=='') $bt_class='class="table table-striped table-bordered table-hover table-sm"'; 
  $is_head=is_array($head)&&count($head)>0;
  $is_content=(count($content)>0);
  for ($hlav='',$L=$is_head?array_keys($head):($is_content?array_keys($content[0]):array('0'=>nbsp())),$i=0;
       $i<count($L);
       $i++) {
      $label=(isset($head[$L[$i]])?$head[$L[$i]]:$L[$i]).
      (getpar('_o')==$L[$i].' asc'?bt_icon('caret-down'):(getpar('_o')==$L[$i].' desc'?bt_icon('caret-up'):''));
      if ($L[$i]!='detail' && substr($L[$i],0,1)!='_'){
        $tmp=($postlink?postLink('?'.$context,
                         $label,['_o'=>$L[$i].(getpar('_o')==$L[$i].' asc'?' desc':' asc')],
                         'class="btn btn-light text-primary"')
                       :ahref('?_o='.$L[$i].(getpar('_o')==$L[$i].' asc'?' desc':' asc').$context,
                        $label));
                                        
      }else{
        $tmp=tg('div','class="btn btn-light "',$label);

      }   
      $hlav.=ta('th',$tmp); 
  }              
  $n=0; 
  foreach ($content as $row){
    $rkapsa='';
    for ($i=0;$i<count($L);$i++){
       if (substr($L[$i],0,1)=='_') $L[$i]=substr($L[$i],1);
       $ktisku=isset($row[$L[$i]])?$row[$L[$i]]:'';
       if (gettype($ktisku)=="object" && gettype($ktisku)!="NULL")
         $ktisku=$ktisku->load();
       if ($ktisku=='') $ktisku=nbsp(1);  
       $rkapsa.=ta('td',$ktisku);
     }
     $s.=tg('tr',$n%2?' class="sudy"':'',$rkapsa);
     $n++; 
   }
   if (!$is_content){
     //$s=ta('tr',tg('td','colspan='.count($L),$nodata_text.nbsp()));
     $s=bt_alert($nodata_text,'alert-warning');
   }else{
    /* $filter can include textual expression */
    $s=tg('div','class="table-responsive"',
       tg('table',$bt_class,
        ta('caption',$caption.nbsp(2).ahref('?_se=1'.$context,bt_icon('search').$text_button,'class="btn btn-primary"').
        (getpar('_whr')?tg('span','class="alert alert-success"',isset($filter)?$filter:('Filtrováno: '.urldecode(getpar('_flt')))):'')).
        tg('thead','class="thead-light"',ta('tr',$hlav)).
        ta('tbody',$s)).$pagination);
   }
   return $s;
}


/** pagination for bt_lister
 * 
 *  @param integer $current - first record on the screen
 *  @param integer $total - total records in the set
 *  @param integer $step - number of records on the page
 *  @param string  $context - context paramaters in the page
 *  @param bool  $postlink - when true, generater links are POSTed
 * 
 */
function bt_pagination($current,$total,$step,$context='',$postlink=false){
  $s='';
  $offset='_ofs';
  if (!$total) return '';

  /* posun o stranku zpet */
  if ($current>$step) $s.=tg('li','class="page-item"',
           $postlink?postLink('?'.$context,bt_icon('left'),[$offset=>$current-$step,'_o'=>getpar('_o')],'class="page-link"'):
           tg('a','class="page-link" href="?'.$offset.'='.($current-$step).$context.'"',bt_icon('left')));
  for($i=1;$i<=$total;$i+=$step){
    if ($i==$current){
      /* aktualni stranka je zvyraznena inverzi a nema odkaz */
        $s.=tg('li','class="page-item active "',
             tg('span','class="page-link"',
              ceil($i/$step).tg('span','class="sr-only"','current')));
    }else{
       /* pokud je stranek hodne, mezilehle se nezobrazuji */
        if ((abs($current-$i)<4*$step) || 
            ($current+$i<6*$step) || 
            abs($i)<=$step || 
            abs($total-$i)<$step)
          $s.=tg('li','class="page-item"',
          $postlink?postLink('?'.$context,ceil($i/$step),[$offset=>$i,'_o'=>getpar('_o')],'class="page-link"'):
                    tg('a','class="page-link" href="?'.$offset.'='.$i.$context.'"',ceil($i/$step).' ') );
    }
  }

  /* posun o stranku vpred */
  if ($total-$current>=$step) $s.=tg('li','class="page-item"',
     $postlink?postLink('?'.$context,bt_icon('right'),[$offset=>$current+$step,'_o'=>getpar('_o')],'class="page-link"'):
       tg('a','class="page-link" href="?'.$offset.'='.($current+$step).$context.'"',bt_icon('right')));
  
  /* informacni text o zaznamech */
  $nstran=ceil(($i-1)/$step);
  $stran=($nstran>4?'stran':($nstran>1?'strany':($nstran==1?'strana':'stran'))); 
  $zaznamu=($total>4?'záznamů':($total>1?'záznamy':($total==1?'záznam':'záznamů')));       
  $s=tg('nav','aria-label="..."',
      la("Celkem $total $zaznamu ($nstran $stran) ",
         "Total $total record".($total>1?'s':'')." ($nstran page".($nstran>1?'s':'').") ").
      (($total>$step)?tg('ul','class="pagination"',$s):''));

  return $s;
}

/** The function returns the HTML tag for a range input
 * @param string $label - label before the tag
 * @param string $name - the name of the input tag (name parameter in the form and also the id in the document)
 * @param int $value 
 * @param int $min
 * @param int $max
 * @param string $add - other added parametres in the tag (useful for javascript client-side functionality or styling) 
 * @return string HTML  */
 
function bt_range($label,$name,$min=0,$max=100,$step=1,$value=0,$add=''){
  return tg('label','for="'.$name.'"',$label).
    tg('input','type="range" id="'.$name.'" name="'.$name.'" min="'.$min.'" max="'.$max.
     '" step="'.$step.'" value="'.$value.'" '.$add,
     'noslash');
}

/**
 * input combo for multiple options 
 * @param string $lab - label
 * @param string $id - DOM id in the HTML document and also form field name
 * @param array  $options - result of bt_getoptions function
 * @param array  $data - selected/unselected options
 * @param array $settings
 */ 
function bt_multiselect($lab,$id,$options,$data=[],
          $settings=[
            "disableSelectAll"=>true, 
            "maxHeight"=> 200, 
            "search"=> true,
            "translations"=>["all"=>"","items"=>"položek","selectAll"=>"Označ vše","clearAll"=>"Zruš označení"]]){
  $s='';
  M5::puthf(
    tg('link','href="'.M5::get('path_relative').'/vendor/vanillaSelectBox/vanillaSelectBox.css" rel="stylesheet"')."\n".
    tg('script','src="'.M5::get('path_relative').'/vendor/vanillaSelectBox/vanillaSelectBox.js"',' '),
    'vanilaselect'
  );
  $d=array();
  if (is_array($data)) for($i=0;$i<count($data);$i++){
    $d[$data[$i]]=1;
  }
  foreach ($options as $k=>$v ){
    $s.=tg('option','value="'.$k.'"'.(isset($d[$k])?' selected':''),$v); 
  }
  $s=$lab.' '.tg('select','id="'.$id.'" name="'.$id.'[]" multiple size="20"',$s).  
      ta('script','selectBox'.$id.' = new vanillaSelectBox("#'.$id.'", '.json_encode($settings,JSON_UNESCAPED_UNICODE).');');
      /* for PHP are essential [] after parametr name in the form */
  return $s;
}

/**
 * input combo for one options 
 * @param string $lab - label
 * @param string $id - DOM id in the HTML document and also form field name
 * @param array  $options - result of bt_getoptions function
 * @param array  $data - selected/unselected options
 * @param array $settings
 * @param array $colors - color schema (classes) for individual options
 */ 
function bt_select($lab,$id,$options,$data='',
          $settings=[
            "disableSelectAll"=>true, 
            "maxHeight"=> 200, 
            "search"=> true,
            "translations"=>["all"=>"","items"=>"položek","selectAll"=>"Označ vše","clearAll"=>"Zruš označení"]],
            $colors=[]){
  $s='';
  M5::puthf(
    tg('link','href="'.M5::get('path_relative').'/vendor/vanillaSelectBox/vanillaSelectBox.css" rel="stylesheet"')."\n".
    tg('script','src="'.M5::get('path_relative').'/vendor/vanillaSelectBox/vanillaSelectBox.js"',' '),
    'vanilaselect'
  );
  foreach ($options as $k=>$v ){
    $s.=tg('option','value="'.$k.'"'.($data==$k?' selected':'').(isset($colors[$k])?' class="'.$colors[$k].'" ':''),$v); 
  }
  $s=$lab.' '.tg('select','id="'.$id.'" name="'.$id.'" ',$s).  
      ta('script','selectBox'.$id.' = new vanillaSelectBox("#'.$id.'", '.json_encode($settings,JSON_UNESCAPED_UNICODE).');');
      /* for PHP are essential [] after parametr name in the form */
  return $s;
}

/**
 * helper function for obtaining data into multiselect
 * @param object $db - Database 
 * @param string $sql - generationg SQL
 */ 
function bt_getoptions($db,$sql){
  $r=array();
  $db->Sql($sql);
  while ($db->FetchRowA()){
    $r[$db->Data(0)]=$db->Data(1);
  }
  return $r;
}

/** ComboBox with the abitity of typing a new value
 * this compoment is independent on Bootstrap CSS !
 * @param string $lab label
 * @param string $id field identifier
 * @param array list of available options, use function to_hash() to generate it from an SQL command
 * @param string $val default value
 */
function bt_comboauto($lab,$id,$data=[],$val=''){
  M5::puthf(
    tg('link','href="'.M5::get('path_relative').'/vendor/comboAutocomplete/cbac.css" rel="stylesheet"')."\n".
    tg('script','src="'.M5::get('path_relative').'/vendor/comboAutocomplete/cbac.js"',' '),
    'comboauto'
  );
  $s='';
  foreach ($data as $k=>$v) $s.=tg('li','id="'.$id.'_'.$k.'" role="option"',$v);
  $s=tg('div','class="combobox combobox-list"',
      tg('div','class="group"',
       tg('input','id="'.$id.'-input" name="'.$id.'" class="cb_edit" type="text" role="combobox" aria-autocomplete="list" '.
                  'aria-expanded="false" aria-controls="'.$id.'-listbox" value="'.$val.'"','noslash').
       tg('button','id="'.$id.'-button" tabindex="-1" aria-label="States" aria-expanded="false" aria-controls="'.$id.'-listbox" type="button"',
       '<svg width="18" height="16" aria-hidden="true" focusable="false" style="forced-color-adjust: auto">
        <polygon class="arrow" stroke-width="0" fill-opacity="0.75" fill="currentcolor" points="3,6 15,6 9,14"></polygon>
        </svg>'      
        )).
       tg('ul','id="'.$id.'-listbox" role="listbox" aria-label="'.$lab.'"',$s)); 
  if ($lab!='') $s=tg('label','for="'.$id.'"',$lab).$s;   
  
  return $s;
}

/** The Function returns HTML form submit button  
 * @param string $name - the name of the tag
 * @param string $title - the visible label on the button
 * @param string $class - the CSS class for special buttons
 * @return string HTML  */
 
 function bt_button($name,$value,$class='btn btn-primary',$title=''){
    return tg('button','name="'.$name.'" value="'.$value.'" class="'.$class.'"',$title!=''?$title:$value).para($name,$value);
 }

 /** The button with ability fill-in given inputs with current Krovak coordinates
  * 
 */

 function bt_position_krovak($name_x, $name_y, $label=''){
  M5::puthf(
          tg('script','src="'.M5::get('path_relative').'/vendor/position/position.js"',' ').
          tg('script','src="'.M5::get('path_relative').'/vendor/position/proj4.js"',' '),
          'position'
  );


  $r=tg('button','type="button" class="btn btn-primary" '.
                 'onclick="getLocation(\''.$name_x.'\',\''.$name_y.'\');"',$label!=''?$label:bt_icon('geolocation'));
  //$r.=tg('span','id="status"','');
  return $r;
}

/** Modal javascript window with the ability render server-controled content, and repeated interaction  
 * @param string $name - element id for target field for returned response 
 * @param string $id - element id
 * @param string $text caller button text
 * @param string $title - modal window title
 * @param string $id_widnow - modal window elemnt id
 * @param string $url_ajax - URL to the PHP script, which generate dynamicaly the Window content, on request
 * @return string a HTML component to use it in page generation
 * 
*/
function bt_modal_win($name,
                      $id,
                      $text='Click me',
                      $title='Window',
                      $id_window='empModal',
                      $url_ajax='?ajax=1'){
  
  $r=tg('a','class="btn btn-secondary m-1" id="'.$id.'" ',$text);

  $script=tg('script','type="text/javascript"',
   "$(document).ready(function(){
      $('#".$id."').click(function(){ $('#$id_window').modal('show');
        /* action to fill the modal window */
        var tmp=$('#$name').val();
        /* zobraz obsah okna na zaklade url */
        $.ajax({
               url: '".$url_ajax."',
               type: 'get',
               data: {data: tmp, id_window: '$id_window', name: '$name' },
               success: function(response){ 
                        // Add response in Modal body
                          //$('.modal-body').html(response);                            
                          $('#".$id_window."_inner').html(response);
                        }
         });
      });
      
      
              
   });
   
   /** volano v dodanych ajax datech, ale definovano na urovni dokumentu */
   function href_$id_window(url,data){
       $.ajax({
        url: url,
        type: 'get',
        data: {data: data, id_window: '$id_window', name: '$name'},
        success: function(response){ 
                //$('.modal-body').html(response);                            
                $('#".$id_window."_inner').html(response);
               }
       });}"     
     
  );

  $modal=tg('div','class="modal fade" id="'.$id_window.'" role="dialog"',
          tg('div','class="modal-dialog"',     
           tg('div','class="modal-content"',
            tg('div','class="modal-header"',
               tg('h4','class="modal-title"',$title).
               tg('button','type="button" class="close" data-dismiss="modal"','&times;')).
            tg('div','class="modal-body" id="'.$id_window.'_inner"' , ' ').
               tg('div','class="modal-footer"',
               tg('button','type="button" class="btn btn-primary" data-dismiss="modal"','OK')))));

  return $r.$script.$modal;
}
?>