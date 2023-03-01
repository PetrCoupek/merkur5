<?php
/** Merkur 5 test ESRI map dialog
 * @author Petr Coupek
 * @date 16.02.2023
 */

include_once '../lib/mlib.php';
M5::set('header','Map test 4');
M5::skeleton('../');
M5::puthf(
 tg('link','rel="stylesheet" href="https://js.arcgis.com/4.25/esri/themes/light/main.css"').
 tg('script','src="https://js.arcgis.com/4.25/"',' ').
 ta('script', <<<EOT
  require([
    "esri/config",
    "esri/Map",
    "esri/views/MapView",
    "esri/layers/MapImageLayer",
    "esri/Basemap",
    "esri/Graphic",
    "esri/layers/GraphicsLayer"

    ], function(esriConfig,Map, MapView, MapImageLayer, Basemap, Graphic, GraphicsLayer) {

  //esriConfig.apiKey = "YOUR_API_KEY";
 var podklad=new MapImageLayer({
        url: "https://mapy.geology.cz/arcgis/rest/services/Topografie/ZABAGED_komplet/MapServer",
        title: "Basemap" });
 console.log(podklad);       

 var basemap = new Basemap({
    baseLayers: [podklad],
    title: "basemap",
    id: "basemap"});
    
 var map= new Map({
   basemap: basemap
   });

  const view = new MapView({
      container: "viewDiv", // Reference to the view div created in step 5
      map: map, // Reference to the map object created before the view
      zoom: 10
      //, // Sets zoom level based on level of detail (LOD)
      //center: [document.getElementById('X').value, 
      //         document.getElementById('Y').value] // Sets center point of view using longitude,latitude
    });
 

 /* vyznamceni bodu */
    const graphicsLayer = new GraphicsLayer();
    
    map.add(graphicsLayer);
        
    const point = { //Create a point
     type: "point",
     longitude: parseFloat(document.getElementById('X').value),
     latitude: parseFloat(document.getElementById('Y').value),
     x:parseFloat(document.getElementById('Y').value),
     y:parseFloat(document.getElementById('X').value)
    };
    
    console.log(point);
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
    
    console.log(graphicsLayer); 

  }); 
 
 
EOT
 ),
 'maptest');
         
htpr(//tg('div','style="width: 300px; height: 300px;"',
  textfield('X','X',15,15,'-600000','id="X"'), 
  textfield('Y','Y',15,15,'-1100000','id="Y"'),
  tg('div','id="viewDiv" style="width: 800px; height: 800px;"' ,'')
  //)
  );   

M5::done();

?>