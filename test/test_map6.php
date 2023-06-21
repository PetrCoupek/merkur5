<?php
/** Merkur 5 test ESRI map dialog
 * @author Petr Coupek
 * @date 05.06.2023
 */

include_once '../lib/mlib.php';
M5::set('header','Map test 6');
M5::set('debug',true);
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
    "esri/layers/GraphicsLayer",
    "esri/geometry/SpatialReference",
    "esri/widgets/Editor"

    ], function(esriConfig,Map, MapView, MapImageLayer, Basemap, Graphic, GraphicsLayer, SpatialReference, Editor) {

  //esriConfig.apiKey = "YOUR_API_KEY";
 var podklad=new MapImageLayer({
        url: "https://mapy.geology.cz/arcgis/rest/services/Topografie/ZABAGED_komplet/MapServer",
        title: "Basemap" });
 console.log(podklad);       

 var basemap = new Basemap({
    baseLayers: [podklad],
    title: "basemap",
    id: "basemap"});
    
 var map = new Map({
   basemap: basemap
   });

   const view = new MapView({
      container: "viewDiv", // Reference to the view div created in step 5
      map: map, // Reference to the map object created before the view
      center: [document.getElementById('Y').value, 
               document.getElementById('X').value], // Sets center point of view using longitude,latitude
      zoom: 10  // Sets zoom level based on level of detail (LOD)         
    });
 

 /* vyznamceni bodu */
    const graphicsLayer = new GraphicsLayer();
    
    map.add(graphicsLayer);
      
        
    // Create a line geometry with the coordinates 
    var tx=parseFloat(document.getElementById('X').value);
    var ty=parseFloat(document.getElementById('Y').value);
    const lineSymbol = {
      type: "simple-line", // autocasts as new SimpleLineSymbol()
      color: [226, 119, 40], // RGB color values as an array
      width: 4
    };
    
    const line={ type:"polyline",
                 paths: [[tx-20000, ty-200],
                         [tx+200, ty+20000]
                        ],
                spatialReference: { wkid: 5514  }
               };
    
    const lineGraphic = new Graphic({
      geometry: line, // Add the geometry created in step 3
      symbol: lineSymbol, // Add the symbol created in step 4
      attributes: [] // Add the attributes created in step 5
    });
    
    graphicsLayer.add(lineGraphic); 
    
    view.when(() => {
          view.map.loadAll().then(() => {
            view.map.editableLayers.forEach((layer) => {
              if (layer.type === "feature") {
                switch (layer.geometryType) {
                  case "polygon":
                    polygonLayer = layer;
                    break;
                  case "polyline":
                    lineLayer = layer;
                    break;
                  case "point":
                    pointLayer = layer;
                    break;
                }
              }
            });

            // Create layerInfos for layers in Editor. This
            // sets the fields for editing.

            const pointInfos = {
              layer: pointLayer,
              formTemplate: {
                // autocasts to FormTemplate
                elements: [
                  {
                    // autocasts to Field Elements
                    type: "field",
                    fieldName: "HazardType",
                    label: "Hazard type"
                  },
                  {
                    type: "field",
                    fieldName: "Description",
                    label: "Description"
                  },
                  {
                    type: "field",
                    fieldName: "SpecialInstructions",
                    label: "Special Instructions"
                  },
                  {
                    type: "field",
                    fieldName: "Status",
                    label: "Status"
                  },
                  {
                    type: "field",
                    fieldName: "Priority",
                    label: "Priority"
                  }
                ]
              }
            };

            const lineInfos = {
              layer: lineLayer,
              formTemplate: {
                // autocasts to FormTemplate
                elements: [
                  {
                    // autocasts to FieldElement
                    type: "field",
                    fieldName: "Severity",
                    label: "Severity"
                  },
                  {
                    type: "field",
                    fieldName: "blocktype",
                    label: "Type of blockage"
                  },
                  {
                    type: "field",
                    fieldName: "fullclose",
                    label: "Full closure"
                  },
                  {
                    type: "field",
                    fieldName: "active",
                    label: "Active"
                  },
                  {
                    type: "field",
                    fieldName: "locdesc",
                    label: "Location Description"
                  }
                ]
              }
            };

            const polyInfos = {
              layer: polygonLayer,
              formTemplate: {
                // autocasts to FormTemplate
                elements: [
                  {
                    // autocasts to FieldElement
                    type: "field",
                    fieldName: "incidenttype",
                    label: "Incident Type"
                  },
                  {
                    type: "field",
                    fieldName: "activeincid",
                    label: "Active"
                  },
                  {
                    type: "field",
                    fieldName: "descrip",
                    label: "Description"
                  }
                ]
              }
            };

            const editor = new Editor({
              view: view,
              layerInfos: [pointInfos, lineInfos, polyInfos]
            });

            // Add the widget to the view
            view.ui.add(editor, "top-right");
          });
        });
    
    
    
     

  }); 
 
 
EOT
 ),
 'maptest');
         
htpr(//tg('div','style="width: 300px; height: 300px;"',
  tg('form','method="post" action="?" class="bg-light p-2 border" ',
    gl(textfield('x','X',10,10,getpar('X')?getpar('X'):'-600000','id="X"'), 
      textfield(' y','Y',10,10,getpar('Y')?getpar('Y'):'-1100000','id="Y"'),
      nbsp(1),submit("center",'OK',"btn btn-primary"),
      tg('div','id="viewDiv" style="width: 800px; height: 800px;"' ,'')
    )
  )  
  //)
  );   
deb(getpar('X').'; '.getpar('Y'));
M5::done();

?>