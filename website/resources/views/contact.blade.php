
@include('layouts.header')
<body>

    @include('layouts.header-navbar')

    <div class="body-content font-color">
        <div class="col-sm-4" ><h1>Contact Us</h1>
        <ul class="list-group">
            <li class="list-group-item">SticksAndStocks Ltd Pty</li>
            <li class="list-group-item">PH:963424</li>
            <li class="list-group-item">Address: 435-457 Swanston St, Melbourne VIC 3000</li>
        </ul>
        </div>
        <div class="col-sm-4" >
        <div id="map" style="width:400px;height:400px;"></div>
        <script>
            function initMap() {
                var uluru = {lat: -37.8084349, lng: 144.9630036};
                var map = new google.maps.Map(document.getElementById('map'), {
                    zoom: 17,
                    center: uluru
                });
                var marker = new google.maps.Marker({
                    position: uluru,
                    map: map
                });
            }
        </script>
        <script async defer
                src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBcNXgoLSLCzOikdkaCNQQYiYXM9H_WJiA&callback=initMap">
        </script>
    </div>
</div>
    @include('layouts.footer')
</body>