<?php
/** Merkur 5 hello world application
 * @author Petr Coupek
 */

include_once '../lib/mlib.php';

class Hello extends M5{

  static function skeleton($path=''){
    self::set('header','Minimal application');  /* page header */ 
    parent::skeleton();                         /* implicit route */
    htpr(ta('h1','Minimal application'),        /* */
         ta('p','That\'s it !'));
    self::done();                               /* buffer write */       
  }

} 

/* singleton design */

Hello::skeleton();                            


?>