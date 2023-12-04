<?php
/** Merkur 5 test clock - how to integrate third-party frontend
 * @author Petr Coupek
 * @date 09.11.2023
 */

include_once '../lib/mlib.php';
include_once '../lib/mbt.php';
M5::set('header','Test clock');

M5::skeleton('../');
htpr(march_clock());

htpr(march_clock('{tick:false, tz:8, diameter:10, noFace:true }','second'));

htpr(march_clock('{tick:true, tz:-2 }','third'));

htpr(br(),march_clock(null,'fourht',500,500));
               
M5::done();

/** function realize a front-end based canvas technology reali time clock 
 *
 *
 */

function march_clock($pars='',$id='clockCanvas',$width=100,$height=100){
  M5::puthf(tg('script','src="var.extensions/march.js"',''),
            'march_clock');
  $r=tg('script', 'type="text/javascript"',
  '$(window).bind("load", function() {
    try {
      startClock("'.$id.'"'.($pars!=''?',':'').$pars.');
    } catch(e) {
      // in Internet Explorer there is no canvas!
      document.getElementById( "'.$id.'" ).style.display = \'none\';
    }
    });');
  $r.=tg('canvas','width="'.$width.'" height="'.$height.'" id="'.$id.'"','no support ..');
  
  return $r;
}

?>