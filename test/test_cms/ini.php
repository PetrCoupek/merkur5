<?php
/** Common ini file for tests. It stores the hidable - not to push it to the git
 * It contains cod for :
 *  - Microbe complex application template - ini file
 *  - individual defining og constsnts for other 
 * @author Petr Coupek
 * @date 06.11.2020 16.11.2022 
 */

/* sekce pro ulozeni hesel, ktere se nahraji spolu s mlib.php */
define('PASS_DAT_SUR','nu2sa*2420a');
/* ---------------------------------------------------------- */

/* sekce pro d.php  */
$dbconnect_cms='file=../../data/m5.sqlite3,mode=1';

$verse='1.0';
$navbar=tg('nav','class="navbar navbar-expand-sm navbar-light bg-light border-bottom p-0 mt-0 ml-0"',
         tg('button','class="btn" id="menu-toggle"','=').nbsp(3).
         ' #BREADCRUMB# '.
         tg('button','class="navbar-toggler" type="button" data-toggle="collapse" 
                data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" 
                aria-expanded="false" aria-label="Toggle navigation"',
           tg('span','class="navbar-toggler-icon"',' &nbsp; ')).
         tg('div','class="collapse navbar-collapse" id="navbarSupportedContent"',
           tg('ul','class="navbar-nav ml-auto mt-2 mt-lg-0"',
             tg('li','class="nav-item"',
               tg('a','class="nav-link" href="?item=1"','Domů')
             ).
             tg('li','class="nav-item"',
               '#EDIT_LINK#'
             ). 
             tg('li','class="nav-item"',
               tg('a','class="nav-link" href="?logout=1"','Odhlášení')))));
global $htptemp;
$path='../../';
$htptemp='<!DOCTYPE HTML >'."\n".
   ta('html',
    ta('head',
     ta('title','Merkur 5 complex application').
     tg('meta','http-equiv="content-type" content="text/html; charset=utf-8"').
     tg('meta','name="language" content="cs"').
     tg('meta','name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no"').
     tg('meta','name="description" lang="en" content="Merkur 5 appliaction template"').
     tg('meta','name="keywords" lang="cs" content="Vzorová aplikace"').
     tg('meta','name="author" content="Petr Čoupek"').
     tg('link','rel="stylesheet" media="screen" href="'.$path.'vendor/bootstrap/css/bootstrap.css" type="text/css" ','noslash').
     //tg('link','rel="stylesheet" media="screen" href="vendor/sidebar/simple-sidebar.css?a=15" type="text/css" ','noslash').
     //tg('link','rel="stylesheet" media="screen" href="vendor/datepicker/css/bootstrap-datepicker3.css" type="text/css" ','noslash').
     tg('link','rel="stylesheet" media="screen" href="'.$path.'css/m5.css" type="text/css" ','noslash').
     //tg('script','type="text/javascript" src="js/lib.js"',' ').
     tg('script','type="text/javascript" src="'.$path.'vendor/jquery/jquery.min.js"',' ').
     tg('script','type="text/javascript" src="'.$path.'vendor/bootstrap/js/bootstrap.bundle.min.js"',' ').
     //tg('script','type="text/javascript" src="vendor/datepicker/js/bootstrap-datepicker.js"',' ').
     //tg('script','type="text/javascript" src="vendor/datepicker/js/locales/bootstrap-datepicker.cs.js"',' ').
     //tg('script','type="text/javascript" src="vendor/autocomplete/bootstrap-autocomplete.min.js"',' ').
     ' #HEADERADD# '
    ).                                           
    tg('body','style="padding-top: 3.5rem;"',
      tg('nav','class="navbar navbar-expand-ld navbar-dark bg-dark fixed-top"',
       tg('a', 'class="navbar-brand" href="?"','#HEADER#').
       tg('a','href="?help=1" target="napoveda"','Dokumentace')
      ).
      tg('main', 'role="main" ',  
       tg('div',
       'id="wrapper" class="d-flex small" ',  /* podstatne je nastaveni tridy small pro cely wrapper*/
       '#SIDEBAR#'.
       tg('div',
         'id="page-content-wrapper"',
         '#UPPERBAR#'.
       tg('div',
         ' class="container-fluid"','#BODY#'))
      ).
      tg('div','class="d-flex justify-items-end"',
      "© Petr Čoupek, 2022, verse: $verse").
       '#ERRORS#'.
      tg('div','class="m5-loader"',
       tg('div','class="d-flex justify-content-center"',
        tg('div','class="spinner-border text-primary big"',' '))).
      tg('script','type="text/javascript"',
         '$("#menu-toggle").click(function(e) { e.preventDefault(); $("#wrapper").toggleClass("toggled"); });'
      )).
      
      tg('script','type="text/javascript"',
         '$(window).bind("beforeunload", function(){
           document.body.style.opacity=0.6;
          $(".m5-loader").css("visibility","visible");          
           });'
      )
  
    )
  );              
           
?>