<?php
/** Merkur 5 test ESRI map dialog
 * @author Petr Coupek
 * @date 17.01.2023
 */

include_once '../lib/mlib.php';
M5::set('header','Map test');
M5::skeleton('../');
M5::puthf(
 tg('link','rel="stylesheet" href="https://js.arcgis.com/4.25/esri/themes/light/main.css"').
 tg('script','src="https://js.arcgis.com/4.25/"',' ').
 ta('script','
   require(["esri/Map", "esri/views/MapView"], (Map, MapView) => {
       const map = new Map({
          basemap: "topo-vector"
        });

        const view = new MapView({
          container: "viewDiv",
          map: map,
          zoom: 6,
          center: [16, 49] // longitude, latitude
        });
      });
 '),
 'maptest');
         
htpr(tg('div','id="viewDiv" class="vw-100 vh-100"' ,''));

M5::done();

?>