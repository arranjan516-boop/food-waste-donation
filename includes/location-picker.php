<?php
// includes/location-picker.php
// Reusable location picker: GPS + Map (OpenStreetMap + Nominatim).
// Usage: include this file where you want the picker to appear,
// then call render_location_picker() with the field prefix you use.
//
// Requires: $old array with keys city, area, pincode, latitude, longitude
//           (or pass them explicitly).

function render_location_picker($old = [], $prefix = '') {
    $city    = htmlspecialchars($old['city']    ?? '', ENT_QUOTES);
    $area    = htmlspecialchars($old['area']    ?? '', ENT_QUOTES);
    $pincode = htmlspecialchars($old['pincode'] ?? '', ENT_QUOTES);
    $lat     = htmlspecialchars($old['latitude']  ?? '', ENT_QUOTES);
    $lng     = htmlspecialchars($old['longitude'] ?? '', ENT_QUOTES);
    $uid     = $prefix . uniqid();
    ?>
    <div class="location-picker" id="lp-<?= $uid ?>">
        <div class="lp-buttons">
            <button type="button" class="btn btn-outline btn-sm" onclick="lpUseGPS('<?= $uid ?>')">
                📍 Use my current location
            </button>
            <button type="button" class="btn btn-outline btn-sm" onclick="lpOpenMap('<?= $uid ?>')">
                🗺️ Pick location on map
            </button>
            <span class="text-muted" id="lp-status-<?= $uid ?>"></span>
        </div>

        <div class="lp-map-wrap" id="lp-map-wrap-<?= $uid ?>" style="display:none">
            <div id="lp-map-<?= $uid ?>" class="lp-map"></div>
            <p class="text-muted" style="font-size:12px;margin-top:6px">
                🖱️ Click anywhere on the map to drop the pin. Drag the pin to fine-tune.
            </p>
        </div>

        <input type="hidden" name="latitude"  id="lp-lat-<?= $uid ?>" value="<?= $lat ?>">
        <input type="hidden" name="longitude" id="lp-lng-<?= $uid ?>" value="<?= $lng ?>">
    </div>

    <script>
    // ---- Only run this bootstrap once per page ----
    if (typeof window.lpInit === 'undefined') {
        window.lpRegistry = {};
        window.lpInit = true;

        // Load Leaflet CSS + JS once
        (function() {
            if (!document.getElementById('leaflet-css')) {
                var l = document.createElement('link');
                l.id = 'leaflet-css';
                l.rel = 'stylesheet';
                l.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                document.head.appendChild(l);
            }
            if (!document.getElementById('leaflet-js')) {
                var s = document.createElement('script');
                s.id = 'leaflet-js';
                s.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                s.defer = true;
                document.head.appendChild(s);
            }
        })();
    }

    // ---- GPS reverse geocoding ----
    function lpUseGPS(uid) {
        var status = document.getElementById('lp-status-' + uid);
        if (!navigator.geolocation) { status.textContent = 'Geolocation not supported'; return; }
        status.textContent = 'Detecting…';
        navigator.geolocation.getCurrentPosition(
            function(pos) {
                var lat = pos.coords.latitude.toFixed(6);
                var lng = pos.coords.longitude.toFixed(6);
                document.getElementById('lp-lat-' + uid).value = lat;
                document.getElementById('lp-lng-' + uid).value = lng;
                status.textContent = '✅ Coordinates captured, looking up city…';
                lpReverseGeocode(lat, lng, uid);
            },
            function(err) { status.textContent = '⚠️ ' + err.message; },
            { enableHighAccuracy: true, timeout: 8000 }
        );
    }

    // ---- Reverse geocoding via OpenStreetMap Nominatim ----
    function lpReverseGeocode(lat, lng, uid) {
        var url = 'https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat +
                  '&lon=' + lng + '&zoom=14&addressdetails=1';
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.address) return;
                var a = data.address;
                // Try to pick the most sensible city/area
                var city = a.city || a.town || a.village || a.suburb || a.county || '';
                var area = a.suburb || a.neighbourhood || a.city_district || a.village || a.hamlet || '';
                var pin  = a.postcode || '';
                var addr = data.display_name || '';

                // Fill the visible fields on the page (best-effort by common names)
                var cityField = document.querySelector('input[name="city"], #city');
                var areaField = document.querySelector('input[name="area"], #area');
                var pinField  = document.querySelector('input[name="pincode"], #pincode');
                var addrField = document.querySelector('textarea[name="address"], input[name="address"], #address');

                if (cityField && !cityField.value) cityField.value = city;
                if (areaField && !areaField.value) areaField.value = area;
                if (pinField  && !pinField.value)  pinField.value  = pin;
                if (addrField && !addrField.value) addrField.value = addr;

                document.getElementById('lp-status-' + uid).textContent = '✅ Location captured';
            })
            .catch(function() {
                document.getElementById('lp-status-' + uid).textContent = '⚠️ Could not look up city name';
            });
    }

    // ---- Map picker ----
    window.lpMaps = window.lpMaps || {};
    function lpOpenMap(uid) {
        document.getElementById('lp-map-wrap-' + uid).style.display = 'block';

        // Ensure Leaflet is loaded
        if (typeof L === 'undefined') {
            setTimeout(function() { lpOpenMap(uid); }, 200);
            return;
        }
        if (window.lpMaps[uid]) return;

        var lat = parseFloat(document.getElementById('lp-lat-' + uid).value) || 13.3379; // Tumkur default
        var lng = parseFloat(document.getElementById('lp-lng-' + uid).value) || 77.1173;

        var map = L.map('lp-map-' + uid).setView([lat, lng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        var marker = L.marker([lat, lng], { draggable: true }).addTo(map);

        function apply(lat, lng) {
            document.getElementById('lp-lat-' + uid).value = lat.toFixed(6);
            document.getElementById('lp-lng-' + uid).value = lng.toFixed(6);
            lpReverseGeocode(lat.toFixed(6), lng.toFixed(6), uid);
        }

        marker.on('dragend', function() {
            var p = marker.getLatLng();
            apply(p.lat, p.lng);
        });

        map.on('click', function(e) {
            marker.setLatLng(e.latlng);
            apply(e.latlng.lat, e.latlng.lng);
        });

        window.lpMaps[uid] = map;
    }
    </script>
    <?php
}
