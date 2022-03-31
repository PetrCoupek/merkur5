<?php
/** Merkur 5 zero application
 * @author Petr Coupek
 * @date 07.02.2022
 */

include_once '../lib/mlib.php';
include_once '../lib/mbt.php';
M5::set('header','Test icon, test mbt ');
M5::skeleton('../');
htpr(ta('h1','Icons'),
    tg('span','class="btn btn-secondary"',bt_icon('floppy-disc')).nbsp(2).
    tg('span','class="btn btn-secondary"',bt_icon('aaa')).nbsp(2). /* non existing */
    tg('span','class="btn btn-secondary"',bt_icon('left')).nbsp(2).
    tg('span','class="btn btn-secondary"',bt_icon('geo-alt')).nbsp(2).
    tg('span','class="btn btn-primary"',bt_icon('right')).nbsp(2).
    tg('span','class="btn btn-primary"',bt_icon('home')).nbsp(2).
    tg('span','class="btn btn-secondary"',bt_icon('file-pdf')).nbsp(2).
    tg('span','class="btn btn-info"',bt_icon('file-word')).nbsp(2).
    tg('span','class="btn btn-light"',bt_icon('file-excel')).nbsp(2).
    tg('span','class="btn btn-warning"',bt_icon('file-text')).nbsp(2).
    tg('span','class="btn btn-warning"',bt_icon('pencil')).nbsp(2).
    tg('span','class="btn btn-success"',bt_icon('floppy-disc')).
    tg('span','class="btn btn-danger"',bt_icon('cross')).nbsp(2).
    tg('span','class="btn btn-secondary"',bt_icon('plusminus')).nbsp(2).
    tg('span','class="btn btn-success"',bt_icon('floppy-add')).nbsp(2).
    tg('span','class="btn btn-success"',bt_icon('plus')).nbsp(2)
    
    );


htpr_all();

?>