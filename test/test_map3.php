<?php
/** Merkur 5 test ESRI map dialog
 * @author Petr Coupek
 * @date 17.01.2023
 */

include_once '../lib/mlib.php';
M5::set('header','Map test 3');
M5::skeleton('../');
M5::puthf(
 tg('link','rel="stylesheet" href="https://js.arcgis.com/4.25/esri/themes/light/main.css"').
 tg('script','src="https://js.arcgis.com/4.25/"',' ').
 ta('script', <<<EOT
  require([
    "esri/config",
    "esri/Map",
    "esri/views/MapView",

    "esri/Graphic",
    "esri/layers/GraphicsLayer"

    ], function(esriConfig,Map, MapView, Graphic, GraphicsLayer) {

  esriConfig.apiKey = "YOUR_API_KEY";

  const map = new Map({
    basemap: "arcgis-topographic" //Basemap layer service
  });

  const view = new MapView({
    map: map,
    center: [-118.80500,34.02700], //Longitude, latitude
    zoom: 13,
    container: "viewDiv"
 });

 const graphicsLayer = new GraphicsLayer();
 map.add(graphicsLayer);

 const point = { //Create a point
    type: "point",
    longitude: -118.80657463861,
    latitude: 34.0005930608889
 };
 const simpleMarkerSymbol = {
    type: "simple-marker",
    color: [226, 119, 40],  // Orange
    outline: {
        color: [255, 255, 255], // White
        width: 1
    }
 };

 const pointGraphic = new Graphic({
    geometry: point,
    symbol: simpleMarkerSymbol
 });
 graphicsLayer.add(pointGraphic);
 
 });
 
 
EOT
 ),
 'maptest');
         
htpr(//tg('div','style="width: 300px; height: 300px;"',
  
  tg('div','id="viewDiv" style="width: 800px; height: 500px;"' ,'')
  //)
  );

  

M5::done();

?>