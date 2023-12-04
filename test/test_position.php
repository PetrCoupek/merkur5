<?php
/** Merkur 5 test position
 * @author Petr Coupek
 * @date 03.08.2023
 */

include_once '../lib/mlib.php';
include_once '../lib/mbt.php';
M5::set('header','Test Position');
M5::set('debug',true);
M5::skeleton('../');

htpr(bt_position_krovak('X','Y'), 
     textfield('X','X',9,12,'',' id="X"'),
     textfield('Y','Y',9,12,'',' id="Y"'));
htpr(hr());

htpr(tg('button','type="button" class="btn btn-primary" '.
                 'onclick="processLocation(listNearest,\'status2\',\'result\');"',
                 bt_icon('geolocation').tg('span','id="status2"','')),
                 
     tg('div','id="result"',' .. '));
                 
M5::done();

?>