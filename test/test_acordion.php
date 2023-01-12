<?php
/** Merkur 5 accordion test
 * @author Petr Coupek
 * */
include_once '../lib/mlib.php';
include_once '../lib/mbt.php';
M5::set('header','Test accordion');
M5::skeleton('../');
htpr('Test',
    bt_accordion([['header'=>'Collapsible Group One','content'=>'body1'],
              ['header'=>'Collapsible Group Two','content'=>'body2'],
              ['header'=>'Collapsible Group Three','content'=>'body3']
              ],
              'aco',
              2 
              ));
M5::done();
?>