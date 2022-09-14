<?php
/** lib_bt.php  - MicroBe core library
 * 
 * Framework library for simplified use the selected Boostrap components 
 *  
 * @author Petr Čoupek
 * @package Merkur5
 * @version 1.2
 * date 11.05.2020 , 30.07.2020, 15.01.2021, 11.11.2021, 7.2.2022, 31.03.2022
 * 07.07.2022 27.07.2022 02.08.2022 01.09.2022 14.09.2022
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
  
  return tg('script','type="text/javascript"',
          ' $( function() {'.
          '   $( "#'.$id.'" ).datepicker({'.$options.'});'.
          '  } );').
        $label.nbsp(1).
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
             tg('button','class="btn btn-primary" data-toggle="collapse" '.
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
 * @param string $label - label before the input
 * @param string $pole - the identifier for the input area
 * @param integer $size - the size in chars
 * @param integer $maxlength - the maximum allowed input size in chars
 * @param string $script - the URL of the script (on server side, in PHP) realizing the asynchronous javascript based response
 * @param string $formname - form name, in which the input tag is placed
 * @param string $value - initial value in the text input
 * @return HTML string */
 
function bt_autocomplete($label,$pole,$size,$maxlength,$script,$formname,$value=''){
  $s=tg('div','class="form-group row"',
      tg('label','for="'.$pole.$formname.'" ',$label).nbsp(1).
      tg('select','class="form-control w-50" name="'.$pole.'" id="'.$pole.$formname.'" '.
    'placeholder="zadejte text..." '.
    'data-noresults-text="Žádný záznam." '.
    'autocomplete="on"',' '));
  $s.=tg('script','type="text/javascript"',
    " $('#".$pole.$formname."').autoComplete(  {
        resolverSettings: { url: '".$script."?', fail:function(){}  },
        resolver: 'ajax',
        minLength: 1
      }
    );
    ");  
  return $s;
}

function bt_autocomplete2($label,$name,$url){
  $r=ta('span',$label).
     tg('select','class="form-control basicAutoSelect" name="'.$name.'" placeholder="zadejte text..." ',' ');
  $r.=ta('script',"$('.basicAutoSelect').autoComplete({
    resolverSettings: {
      url: '".$url."',     
      autocomplete: 'off',
      noResultsText: 'Nic nenalezeno.' },
      minLength: 1});");    

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
 * example : bt_container(
          ['col-1','col-3','col-8'],
          [['row1 col1 text', 'row1 col3 text', 'row1 col8 text'],
           ['row2 col1 text', 'row2 col3 text', 'row2 col8 text'],
           ['row3 col1 text', 'row3 col3 text', 'row3 col8 text']]);
 */
function bt_container($colrules,$rows){
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
    $r.=tg('div','class="row"',$t.' '); /*mezera*/  
  }
  $r=tg('div','class="container"',$r.' '); /*mezera*/
  return $r;
}


/** The bt_hideable area on the page - Bootstrap container helper
 * @param string $label - always visible and clicable label
 * @param string $docid - unique document ID }for javascript functionality)
 * @param string $content - internal HMTL content
 * @param string $addlabel additional parameters in the tabel tag, f.e class="text-decoration-none" class="btn: etc.
 */

function bt_hidable_area($label, $docid, $content, $addlabel=''){
  return 
   tg('div','id="'.$docid.'_o" ',
   ta('h5',tg('a','id="'.$docid.'_c" href="#" '.$addlabel, $label.nbsp(2).bt_icon('caret-down'))).
  
   tg('div','id="'.$docid.'_i" style="display:none" ', $content.
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
 * @param string - icon idenfier ('arrow-right','caret-up' etc.)
*/
function bt_icon($name='info-square'){
  $p1='xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi '.$name.'" viewBox="0 0 16 16" ';
  $p2='version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="16" height="16" fill="currentColor" class="bi '.$name.'" viewBox="0 0 16 16"';
  switch ($name){
  case 'chevron-down':
    return tg('svg',$p1,
     tg('path','fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"'));
  case 'chevron-left':
    return tg('svg',$p1,
     tg('path','fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"'));
  case 'chevron-right':
     return tg('svg',$p1,
     tg('path','fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"'));
  case 'chevron-up':
    return  tg('svg',$p1,
     tg('path','fill-rule="evenodd" d="M7.646 4.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1-.708.708L8 5.707l-5.646 5.647a.5.5 0 0 1-.708-.708l6-6z"'));
  case 'arrow-left':
    return  tg('svg',$p1,
     tg('path','fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"'));
  case 'arrow-right':
    return  tg('svg',$p1,
     tg('path','fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"'));    
  case 'caret-down':
    return  tg('svg',$p1,
     tg('path','d="M3.204 5h9.592L8 10.481 3.204 5zm-.753.659 4.796 5.48a1 1 0 0 0 1.506 0l4.796-5.48c.566-.647.106-1.659-.753-1.659H3.204a1 1 0 0 0-.753 1.659z"'));   
  case 'caret-up':
    return  tg('svg',$p1,
     tg('path','d="M3.204 11h9.592L8 5.519 3.204 11zm-.753-.659 4.796-5.48a1 1 0 0 1 1.506 0l4.796 5.48c.566.647.106 1.659-.753 1.659H3.204a1 1 0 0 1-.753-1.659z"'));  
  case 'check':
    return  tg('svg',$p1,
     tg('path','d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"'));
  case 'check-circle':
    return  tg('svg',$p1,
     tg('path','d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"').
     tg('path','d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z"'));
  case 'geo-alt':
    return  tg('svg',$p1,
     tg('path','d="M12.166 8.94c-.524 1.062-1.234 2.12-1.96 3.07A31.493 31.493 0 0 1 8 14.58a31.481 31.481 0 0 1-2.206-2.57c-.726-.95-1.436-2.008-1.96-3.07C3.304 '.
               '7.867 3 6.862 3 6a5 5 0 0 1 10 0c0 .862-.305 1.867-.834 2.94zM8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10z"').
     tg('path','d="M8 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm0 1a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"'));     
  case 'menu-app':
    return  tg('svg',$p1,
     tg('path','d="M0 1.5A1.5 1.5 0 0 1 1.5 0h2A1.5 1.5 0 0 1 5 1.5v2A1.5 1.5 0 0 1 3.5 5h-2A1.5 1.5 0 0 1 0 3.5v-2zM1.5 1a.5.5 0 0 0-.5.5v2a.5.5 0 0 0 .5.5h2a.5.5 0 0 '.
                '0 .5-.5v-2a.5.5 0 0 0-.5-.5h-2zM0 8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V8zm1 3v2a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2H1zm14-1V8a1 1 0 0 '.
                '0-1-1H2a1 1 0 0 0-1 1v2h14zM2 8.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zm0 4a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1h-6a.5.5 0 0 1-.5-.5z"'));
  case 'power':
    return  tg('svg',$p1,
     tg('path','d="M7.5 1v7h1V1h-1z"').
     tg('path','d="M3 8.812a4.999 4.999 0 0 1 2.578-4.375l-.485-.874A6 6 0 1 0 11 3.616l-.501.865A5 5 0 1 1 3 8.812z"'));
  case 'plusminus':
    return  tg('svg',$p1,
     tg('path','d="m1.854 14.854 13-13a.5.5 0 0 0-.708-.708l-13 13a.5.5 0 0 0 .708.708ZM4 1a.5.5 0 0 1 .5.5v2h2a.5.5 0 0 1 0 1h-2v2a.5.5 0 0 1-1 0v-2h-2a.5.5 0 0 '.
                '1 0-1h2v-2A.5.5 0 0 1 4 1Zm5 11a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5A.5.5 0 0 1 9 12Z"'));
  case 'floppy-add':
    return  tg('svg',$p1,
     tg('path','d="M433.1 129.1l-83.9-83.9C342.3 38.32 327.1 32 316.1 32H64C28.65 32 0 60.65 0 96v320c0 35.35 28.65 64 64 64h320c35.35 0 64-28.65 64-64V163.9C448 152.9 441.7 137.7 433.1 129.1zM224 416c-35.34 0-64-28.66-64-64s28.66-64 64-64s64 28.66 64 64S259.3 416 224 416zM320 208C320 216.8 312.8 224 304 224h-224C71.16 224 64 216.8 64 208v-96C64 103.2 71.16 96 80 96h224C312.8 96 320 103.2 320 112V208z"'));


  /* Moon icons */  
  case 'floppy-disc':
    return tg('svg',$p2,
     tg('path','d="M14 0h-14v16h16v-14l-2-2zM8 2h2v4h-2v-4zM14 14h-12v-12h1v5h9v-5h1.172l0.828 0.828v11.172z"'));
  case 'left':
    return tg('svg',$p2,tg('path','d="M0.5 8l7.5 7.5v-4.5h8v-6h-8v-4.5z"'));
  case 'right':
    return tg('svg',$p2,tg('path','d="M15.5 8l-7.5-7.5v4.5h-8v6h8v4.5z"'));
  case 'home':
    return tg('svg',$p2,tg('path','d="M16 9.5l-3-3v-4.5h-2v2.5l-3-3-8 8v0.5h2v5h5v-3h2v3h5v-5h2z"'));
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
    '0.493-0.029 0.705-0.029 0.387 0 0.502-0.002 0.88 0.095s0.383 0.293 0.318 0.333v0z"'));
  case 'file-word': 
    return tg('svg',$p2,
    tg('path','d="M9.997 7.436h0.691l-0.797 3.534-1.036-4.969h-1.665l-1.205 4.969-0.903-4.969h-1.741l1.767 7.998h1.701l1.192-4.73 1.066 4.73h1.568l2.025-7.998h-2.663v1.435z"').
    tg('path','d="M14.341 3.579c-0.347-0.473-0.831-1.027-1.362-1.558s-1.085-1.015-1.558-1.362c-0.806-0.591-1.197-0.659-1.421-0.659h-7.75c-0.689 0-1.25 0.561-1.25 1.25v13.5c0 '.
    '0.689 0.561 1.25 1.25 1.25h11.5c0.689 0 1.25-0.561 1.25-1.25v-9.75c0-0.224-0.068-0.615-0.659-1.421v0zM12.271 2.729c0.48 0.48 0.856 0.912 1.134 1.271h-2.406v-2.405c0.359 '.
    '0.278 0.792 0.654 1.271 1.134v0zM14 14.75c0 0.136-0.114 0.25-0.25 0.25h-11.5c-0.135 0-0.25-0.114-0.25-0.25v-13.5c0-0.135 0.115-0.25 0.25-0.25 0 0 7.749-0 7.75 0v3.5c0 '.
    '0.276 0.224 0.5 0.5 0.5h3.5v9.75z"'));
  case 'file-excel':
    return tg('svg',$p2,
    tg('path','d="M11.61 6h-2.114l-1.496 2.204-1.496-2.204h-2.114l2.534 3.788-2.859 4.212h3.935v-1.431h-0.784l0.784-1.172 1.741 2.603h2.194l-2.859-4.212 2.534-3.788z"').
    tg('path','d="M14.341 3.579c-0.347-0.473-0.831-1.027-1.362-1.558s-1.085-1.015-1.558-1.362c-0.806-0.591-1.197-0.659-1.421-0.659h-7.75c-0.689 0-1.25 0.561-1.25 '.
    '1.25v13.5c0 0.689 0.561 1.25 1.25 1.25h11.5c0.689 0 1.25-0.561 1.25-1.25v-9.75c0-0.224-0.068-0.615-0.659-1.421v0zM12.271 2.729c0.48 0.48 0.856 0.912 1.134 '.
    '1.271h-2.406v-2.405c0.359 0.278 0.792 0.654 1.271 1.134v0zM14 14.75c0 0.136-0.114 0.25-0.25 0.25h-11.5c-0.135 0-0.25-0.114-0.25-0.25v-13.5c0-0.135 0.115-0.25 '.
    '0.25-0.25 0 0 7.749-0 7.75 0v3.5c0 0.276 0.224 0.5 0.5 0.5h3.5v9.75z"'));
   case 'file-text':
    return tg('svg',$p2,
    tg('path','d="M14.341 3.579c-0.347-0.473-0.831-1.027-1.362-1.558s-1.085-1.015-1.558-1.362c-0.806-0.591-1.197-0.659-1.421-0.659h-7.75c-0.689 0-1.25 0.561-1.25 '.
    '1.25v13.5c0 0.689 0.561 1.25 1.25 1.25h11.5c0.689 0 1.25-0.561 1.25-1.25v-9.75c0-0.224-0.068-0.615-0.659-1.421zM12.271 2.729c0.48 0.48 0.856 0.912 1.134 '.
    '1.271h-2.406v-2.405c0.359 0.278 0.792 0.654 1.271 1.134zM14 14.75c0 0.136-0.114 0.25-0.25 0.25h-11.5c-0.135 0-0.25-0.114-0.25-0.25v-13.5c0-0.135 0.115-0.25 '.
    '0.25-0.25 0 0 7.749-0 7.75 0v3.5c0 0.276 0.224 0.5 0.5 0.5h3.5v9.75z"').
    tg('path','d="M11.5 13h-7c-0.276 0-0.5-0.224-0.5-0.5s0.224-0.5 0.5-0.5h7c0.276 0 0.5 0.224 0.5 0.5s-0.224 0.5-0.5 0.5z"').
    tg('path','d="M11.5 11h-7c-0.276 0-0.5-0.224-0.5-0.5s0.224-0.5 0.5-0.5h7c0.276 0 0.5 0.224 0.5 0.5s-0.224 0.5-0.5 0.5z"').
    tg('path','d="M11.5 9h-7c-0.276 0-0.5-0.224-0.5-0.5s0.224-0.5 0.5-0.5h7c0.276 0 0.5 0.224 0.5 0.5s-0.224 0.5-0.5 0.5z"'));
   case 'pencil':
    return tg('svg',$p2,
    tg('path','d="M6 10l2-1 7-7-1-1-7 7-1 2zM4.52 13.548c-0.494-1.043-1.026-1.574-2.069-2.069l1.548-4.262 2-1.217 6-6h-3l-6 6-3 10 10-3 6-6v-3l-6 6-1.217 2z"'));   
   case 'cross':
    return tg('svg',$p2,
    tg('path','d="M15.854 12.854c-0-0-0-0-0-0l-4.854-4.854 4.854-4.854c0-0 0-0 0-0 0.052-0.052 0.090-0.113 0.114-0.178 0.066-0.178 '.
     '0.028-0.386-0.114-0.529l-2.293-2.293c-0.143-0.143-0.351-0.181-0.529-0.114-0.065 0.024-0.126 0.062-0.178 0.114 0 0-0 0-0 '.
     '0l-4.854 4.854-4.854-4.854c-0-0-0-0-0-0-0.052-0.052-0.113-0.090-0.178-0.114-0.178-0.066-0.386-0.029-0.529 0.114l-2.293 '.
     '2.293c-0.143 0.143-0.181 0.351-0.114 0.529 0.024 0.065 0.062 0.126 0.114 0.178 0 0 0 0 0 0l4.854 4.854-4.854 4.854c-0 0-0 0-0 '.
     '0-0.052 0.052-0.090 0.113-0.114 0.178-0.066 0.178-0.029 0.386 0.114 0.529l2.293 2.293c0.143 0.143 0.351 0.181 0.529 0.114 0.065-0.024 '.
     '0.126-0.062 0.178-0.114 0-0 0-0 0-0l4.854-4.854 4.854 4.854c0 0 0 0 0 0 0.052 0.052 0.113 0.090 0.178 0.114 0.178 0.066 0.386 0.029 '.
     '0.529-0.114l2.293-2.293c0.143-0.143 0.181-0.351 0.114-0.529-0.024-0.065-0.062-0.126-0.114-0.178z"'));
   case 'plus':
     return tg('svg',$p2,
     tg('path','d="M15.5 6h-5.5v-5.5c0-0.276-0.224-0.5-0.5-0.5h-3c-0.276 0-0.5 0.224-0.5 0.5v5.5h-5.5c-0.276 0-0.5 0.224-0.5 '.
      '0.5v3c0 0.276 0.224 0.5 0.5 0.5h5.5v5.5c0 0.276 0.224 0.5 0.5 0.5h3c0.276 0 0.5-0.224 0.5-0.5v-5.5h5.5c0.276 0 '.
      '0.5-0.224 0.5-0.5v-3c0-0.276-0.224-0.5-0.5-0.5z"'));
  case 'search':
     return tg('svg',$p2,
     tg('path','fill="#000000" d="M15.504 13.616l-3.79-3.223c-0.392-0.353-0.811-0.514-1.149-0.499 0.895-1.048 1.435-2.407 1.435-3.893 '.
      '0-3.314-2.686-6-6-6s-6 2.686-6 6 2.686 6 6 6c1.486 0 2.845-0.54 3.893-1.435-0.016 0.338 0.146 0.757 0.499 1.149l3.223 3.79c0.552 '.
      '0.613 1.453 0.665 2.003 0.115s0.498-1.452-0.115-2.003zM6 10c-2.209 0-4-1.791-4-4s1.791-4 4-4 4 1.791 4 4-1.791 4-4 4z"'));   

  /* default, when input doesnt match */
  default:
    return tg('svg',$p2,
    tg('path','d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 '.
     '2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"').
    tg('path','d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 '.
    '1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"'));
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



/** Table list - a page with strutured (database origin) table
 * @param string $caption - table caption
 * @param array $head - a hash keys are table columns an valueas are table header labels. When key begins
 *                      with _ , ti means tah that column } without that _) is not orderable 
 * @param array $content
 * @param string $nodata_text
 * @param string $bt_class
 * @param string $pagination (result of the bt_pagination function) 
 * @param string $content
 * @param bool  $postlink - when true, generater links are POSTed
 * @param string filter -when not null, it prints this text as filter description
 * 
 */
function bt_lister($caption='',
                   $head=[],
                   $content=[[]],
                   $nodata_text='',
                   $bt_class='',
                   $pagination='',
                   $context='',
                   $postlink=false,
                   $filter=null){

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
       $ktisku=$row[$L[$i]];
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
    $s=tg('div','class="table-responsive"',
       tg('table',$bt_class,
        ta('caption',$caption.nbsp(2).ahref('?_se=1'.$context,bt_icon('search'),'class="btn btn-primary"').
        (getpar('_whr')?(isset($filter)?$filter:  ('Filtrováno: '.urldecode(getpar('_flt'))  )):'')).
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
      "Celkem $total $zaznamu ($nstran $stran) ".
      (($total>$step)?tg('ul','class="pagination"',$s):''));

  return $s;
}



?>