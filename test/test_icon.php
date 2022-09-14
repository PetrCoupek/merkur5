<?php
/** Merkur 5 zero application
 * @author Petr Coupek
 * @date 14.09.2022
 */

include_once '../lib/mlib.php';
include_once '../lib/mbt.php';
M5::set('header','Test icon, test mbt ');
M5::skeleton('../');
$cls=['class="btn btn-secondary"','class="btn btn-primary"','class="btn btn-success"',''];

htpr(ta('h1','Icons'),ta('h2','Bootstrap'));
foreach($cls as $class) htpr(
    tg('span',$class,bt_icon('chevron-down')).nbsp(2).
    tg('span',$class,bt_icon('chevron-left')).nbsp(2).
    tg('span',$class,bt_icon('chevron-right')).nbsp(2).
    tg('span',$class,bt_icon('chevron-up')).nbsp(2).
    tg('span',$class,bt_icon('arrow-left')).nbsp(2).
    tg('span',$class,bt_icon('arrow-right')).nbsp(2).
    br(2).
    tg('span',$class,bt_icon('caret-down')).nbsp(2).
    tg('span',$class,bt_icon('caret-up')).nbsp(2).
    tg('span',$class,bt_icon('check')).nbsp(2).
    tg('span',$class,bt_icon('check-circle')).nbsp(2).
    tg('span',$class,bt_icon('geo-alt')).nbsp(2).
    tg('span',$class,bt_icon('menu-app')).nbsp(2).
    tg('span',$class,bt_icon('power')).nbsp(2).
    tg('span',$class,bt_icon('plusminus')).nbsp(2).
    tg('span',$class,bt_icon('floppy-add')).nbsp(2).
    tg('span',$class,bt_icon('')).nbsp(2).
    tg('span',$class,bt_icon('aaaa')).nbsp(2).br(2));

  htpr(ta('h2','Moon'));
  foreach($cls as $class) htpr(
    tg('span',$class,bt_icon('floppy-disc')).nbsp(2).
    tg('span',$class,bt_icon('left')).nbsp(2).
    tg('span',$class,bt_icon('right')).nbsp(2).
    tg('span',$class,bt_icon('home')).nbsp(2).
    tg('span',$class,bt_icon('file-pdf')).nbsp(2).
    tg('span',$class,bt_icon('file-word')).nbsp(2).
    tg('span',$class,bt_icon('file-pdf')).nbsp(2).
    tg('span',$class,bt_icon('file-word')).nbsp(2).
    tg('span',$class,bt_icon('file-excel')).nbsp(2).
    tg('span',$class,bt_icon('file-text')).nbsp(2).
    tg('span',$class,bt_icon('pencil')).nbsp(2).
    tg('span',$class,bt_icon('cross')).nbsp(2).
    
    tg('span',$class,bt_icon('plus')).nbsp(2).
    tg('span',$class,bt_icon('search')).nbsp(2).br(2)
    
    );


htpr_all();

?>