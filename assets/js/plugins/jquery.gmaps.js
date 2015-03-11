;(function load($) {

    $.widget("ui.gmaps", $.klearmatrix.module, {
        widgetEventPrefix:"gmaps",

        options: {
            cache: {},
            draggable: true,
            zoom: 15,
            height: 450,
            width: 500,
            defaultLat : 43.262951899365135,
            defaultLng : -2.9352541503906195
        },

        imgUrl: '//maps.googleapis.com/maps/api/staticmap?center=%lat%,%lng%&zoom=%zoom%&size=%width%x%height%&sensor=false',
        markerUrl: '&markers=color:red%7C%lat%,%lng%',
        readOnly: false,
        geocoder: null,
        map: null,
        marker: null,
        greenPin: 'http://maps.google.com/mapfiles/ms/icons/green-dot.png',

        _setOption: function (name, value) {

            $.Widget.prototype._setOption.apply(this, arguments);
        },
        _create: function() {

            this._loadCacheNodes();

            if (this.readOnly == true) {
                this._initMap();
            } else {
                var self = this;
                if ( !window.google ) {
                    var script = document.createElement("script");
                    script.type = "text/javascript";
                    script.src = "//www.google.com/jsapi?sensor=false&language=es";
                    document.head.appendChild(script);
                }

                (function lazyGoogle() {

                    if (!window.google) {
                        setTimeout(lazyGoogle,450);
                        return;
                    }

                    google.load("maps", "3", {
                        callback: function(){
                            self._initMap();
                            self._bindEvents();
                        },
                        other_params: "sensor=false&language=es"
                    });
                })();
            }
        },

        _loadCacheNodes: function () {
            var context = this.element.klearModule("getPanel");

            this.options.cache.dummy = context;
            this.options.cache.context = context.parent();
            this.options.cache.adress = this.options.cache.context.find("input.auto");
            this.options.cache.lat = this.options.cache.context.find("input.map_lat");
            this.options.cache.lng = this.options.cache.context.find("input.map_lng");
            this.options.cache.canvas = this.options.cache.dummy.parents().find('div.mapCanvas');

            if ( this.options.cache.canvas.data('type') == 'readOnly' ) {
                this.options.cache.canvas.remove();
                this.readOnly = true;
            }

            this.options.cache.canvas.css({width: this.options.width, height: this.options.height});
        },

        _initMap: function () {
            if (this.readOnly ==  true) {
                var printMarker = true;
                var img = this.options.cache.context.find('img.mapImg');

                var lat = this.options.cache.lat.val();
                var lng = this.options.cache.lng.val();

                if (lat == '' || lng == '') {
                    printMarker = false;
                    lat = this.options.defaultLat;
                    lng = this.options.defaultLng;
                    this.imgUrl = this.imgUrl.replace(/%zoom%/g, '1');
                } else {
                    this.imgUrl = this.imgUrl.replace(/%zoom%/g, this.options.zoom);
                }

                this.imgUrl = this.imgUrl.replace(/%lat%/g, lat);
                this.imgUrl = this.imgUrl.replace(/%lng%/g, lng);
                this.imgUrl = this.imgUrl.replace(/%width%/g, this.options.width);
                this.imgUrl = this.imgUrl.replace(/%height%/g, this.options.height);

                if ( printMarker ) {
                    this.markerUrl = this.markerUrl.replace(/%lat%/, lat);
                    this.markerUrl = this.markerUrl.replace(/%lng%/, lng);
                    this.imgUrl = this.imgUrl + this.markerUrl;
                }

                img.attr('src', this.imgUrl);

            } else {

                var self = this;

                this.geocoder = new google.maps.Geocoder();

                var lat = this.options.cache.lat.val();
                var lng = this.options.cache.lng.val();

                if (lat == '' || lng == '') {

                    lat = this.options.defaultLat;
                    lng = this.options.defaultLng;
                }

                var latLng = new google.maps.LatLng(lat, lng);

                this.map = new google.maps.Map(this.options.cache.canvas[0], {
                    zoom: this.options.zoom,
                    center: latLng,
                    mapTypeId: google.maps.MapTypeId.ROADMAP
                });

                this.marker = new google.maps.Marker({
                    position: latLng,
                    title: 'Point A',
                    map: this.map,
                    draggable: this.options.draggable
                });

                // Update current position info.
                this._updateMarkerPosition(latLng);

                // Add dragging event listeners.
                google.maps.event.addListener(this.marker, 'dragstart', function() {
                    self._updateMarkerAddress('Calculando...');
                });

                google.maps.event.addListener(this.marker, 'drag', function() {
                    self._updateMarkerPosition(self.marker.getPosition());
                    self.map.setCenter(self.marker.getPosition());
                });

                google.maps.event.addListener(this.marker, 'dragend', function() {
                    self._geocodePosition(self.marker.getPosition());
                    self.options.cache.adress.trigger('change');
                });

                // Show new marker on Map Move
                var $currentMap = this.map;
                google.maps.event.addListener($currentMap, 'dragend', (function($currentMap){
                    return function() {
                        var center = $currentMap.getCenter();
                        self.marker.setMap(null);
                        var marker = new google.maps.Marker({
                            position: center,
                            map: $currentMap,
                            icon: self.greenPin,
                            draggable: true
                        });
                        $currentMap.setCenter(center);
                        $currentMap.marker = marker;
                        self.marker = marker;
                        google.maps.event.addListener(marker, 'dragend', (function(marker){
                            return function() {
                                marker.setIcon();
                                self._updateMarkerPosition(marker.getPosition());
                                self._geocodePosition(marker.getPosition());
                                self.options.cache.adress.trigger('change');
                            }
                        })(marker));
                    }
                })($currentMap));
            }
        },

        _bindEvents: function() {

            var self = this;

            this.options.cache.context.find("input[data-plugin=gmaps]").keyup(function(e){
                if(e.keyCode == 13)
                {
                    $(this).blur();
                    self._geocode(self.options.cache.dummy.val());
                }
            });

            this.options.cache.adress.on('change', function(){
                self._updateMarkerAddress($(this).val());
            });
            
            if ($('input.visualFilter, select.visualFilter', this.options.cache.context.parents('.klearMatrix_form')).length>0) {
                $('input.visualFilter, select.visualFilter', this.options.cache.context.parents('.klearMatrix_form')).on("change", function(){
                    google.maps.event.trigger(self.map, "resize");
                });
            } 
        },

        destroy: function() {

            // remove classes + data
            $.Widget.prototype.destroy.call( this );
            return this;
        },

        /////////////////////////////////////////////////
        //               utilities:                    //
        /////////////////////////////////////////////////
        _geocode: function(address) {

            var self = this;

            this.geocoder.geocode({
              'address': address,
              'partialmatch': true
            }, function (results, status) {

                if (status == 'OK' && results.length > 0) {

                    self.options.cache.adress.val(self.options.cache.dummy.val());
                    self.map.fitBounds(results[0].geometry.viewport);
                    self.marker.setPosition(results[0].geometry.location);

                    self._updateMarkerPosition(results[0].geometry.location);

                } else {

                    alert("Geocode was not successful for the following reason: " + status);
                }
            });
        },

        _geocodePosition: function (pos) {

            var self = this;

            this.geocoder.geocode({

                latLng: pos

            }, function(responses) {

                if (responses && responses.length > 0) {

                    self._updateMarkerAddress(responses[0].formatted_address);

                } else {

                    self._updateMarkerAddress(null);
                }
            });
        },

        _updateMarkerPosition: function(latLng) {

            this.options.cache.lat.val(latLng.lat());
            this.options.cache.lng.val(latLng.lng());
        },

        _updateMarkerAddress: function(str) {

            this.options.cache.dummy.val(str);
            this.options.cache.adress.val(str);
            //this.options.cache.adress.trigger('change');
        }
    });

    $.widget.bridge("ui.gmaps");

})( jQuery );
