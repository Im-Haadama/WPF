<!DOCTYPE html >
<?php
if ( ! defined( "ABSPATH" ) ) {
	define( 'ABSPATH', dirname(dirname(dirname( dirname( $_SERVER["SCRIPT_FILENAME"])))). '/');
}

require_once(ABSPATH . 'wp-config.php');

$id = GetParam("id", true);
$mission = Freight_Mission_Manager::get_mission_manager($id);
?>
<head>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
    <meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
    <title>תכנון מסלול </title>
    <style>
        /* Always set the map height explicitly to define the size of the div
         * element that contains the map. */
        #map {
            height: 100%;
        }
        /* Optional: Makes the sample page fill the window. */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
    </style>
    <script src="/wp-content/plugins/wpf_flavor/includes/core/gui/client_tools.js"></script>
</head>

<html>
<body>
<div id="map"></div>

<script>
    var post_url = '<?php print Freight::getPost(); ?>';
    var customLabel = {
        Pickup: {
            label: 'P'
        },
        Delivery: {
            label: 'D'
        }
    };
    let the_markers = [];

    function pri_changed(obj)
    {
        // Hide input box
        obj.target.parentElement.parentElement.parentElement.parentElement.style="display:none";

        let id = obj.target.id;
        let other_id = -1;

        let new_pri = obj.target.value;
        let old_pri = obj.target.dataset.pri;

        // Find the second delivery with the new priority
        the_markers.forEach(function(marker) {
            if (marker.id === id) {
                // Set the prioriity for the selected object
                let label =  { text: new_pri };
                marker.setLabel(label);
                marker.pri = new_pri;
            } else

            // Replace the priority of the opposite node
            // var label = new google.maps.label()
            if (marker.pri === new_pri){
                let label =  { text: old_pri };
                other_id = marker.id;
                marker.setLabel(label);
                marker.pri = old_pri;
            }
        })

        obj.target.dataset.pri = new_pri;

        // Send the server to update the data
        let order_id = id.substr(0, id.lastIndexOf('_'));
        let site_id = id.substr(id.lastIndexOf('_') + 1);
        execute_url(post_url + "?operation=order_save_pri&order_id=" + order_id + "&site_id=" +site_id + "&pri="+ new_pri);

        order_id = other_id.substr(0, other_id.lastIndexOf('_'));
        site_id = other_id.substr(other_id.lastIndexOf('_') + 1);
        execute_url(post_url + "?operation=order_save_pri&order_id=" + order_id + "&site_id=" +site_id + "&pri="+ old_pri);


    }

    function initMap() {
        var map = new google.maps.Map(document.getElementById('map'), {
            center: new google.maps.LatLng(<?php print $mission->map_center(); ?>),
            zoom: <?php print $mission->zoom(); ?>
        });
        var infoWindow = new google.maps.InfoWindow;

        // Change this depending on the name of your PHP or XML file
        let url = '<?php print WPF_Flavor::getPost() ?>?operation=mission_markers&id=<?php print $id;?>'
        execute_url(url, function(data) {
            var xml = data.responseXML;
            var xml = data.responseXML;
            if (null == xml) {
                alert ("Empty response from server!");
                return;
            }
            var markers = xml.documentElement.getElementsByTagName('marker');
            Array.prototype.forEach.call(markers, function(markerElem) {
                // Get info from server
                var id = markerElem.getAttribute('id');
                var name = markerElem.getAttribute('name');
                var address = decodeURI(markerElem.getAttribute('address'));
                address = address.replace("/+/g", " ");
                var type = markerElem.getAttribute('type');
                var point = new google.maps.LatLng(
                    parseFloat(markerElem.getAttribute('lat')),
                    parseFloat(markerElem.getAttribute('lng')));
                var pri = markerElem.getAttribute(("pri"));

                var infowincontent = document.createElement('div');
                var strong = document.createElement('strong');
                strong.textContent = name
                infowincontent.appendChild(strong);
                infowincontent.appendChild(document.createElement('br'));

                var text = document.createElement('text');
                text.textContent = address

                var input = document.createElement('input')
                input.setAttribute("id", id);
                input.setAttribute("data-pri", pri);

                var div_input = document.createElement('div')
                div_input.appendChild(input)

                infowincontent.appendChild(text);

                infowincontent.appendChild(div_input);
                // var icon = document.createElement("label"); icon.innerHTML = id;
                var icon = { label: pri } || {};
                // var icon = customLabel[type] || {};
                var marker = new google.maps.Marker({
                    id: id,
                    map: map,
                    position: point,
                    label: icon.label,
                    pri: pri
                });
                the_markers.push(marker);
                marker.addListener('click', function() {
                    infoWindow.setContent(infowincontent);
                    infoWindow.open(map, marker);
                });
            });
        }, null, 1);
        document.addEventListener('change', pri_changed);
    }


    function downloadUrl(url, callback) {
        var request = window.ActiveXObject ?
            new ActiveXObject('Microsoft.XMLHTTP') :
            new XMLHttpRequest;

        request.onreadystatechange = function() {
            if (request.readyState == 4) {
                request.onreadystatechange = doNothing;
                callback(request, request.status);
            }
        };

        request.open('GET', url, true);
        request.send(null);
    }

    function doNothing() {}
</script>
<script defer
        src="https://maps.googleapis.com/maps/api/js?key=<?php print MAPS_KEY; ?>&callback=initMap">
</script>
</body>
</html>
