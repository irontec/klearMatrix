(function load($) {

    $.widget("ui.gmaps", $.klearmatrix.module, {
        widgetEventPrefix:"gmaps",

        options: {
            cache: {},
            draggable: true,
            zoom: 15,
            height: 450,
            width: 500,
            defaultLat : 43.262951899365135,
            defaultLng : -2.9352541503906195,
        },

        imgUrl: 'http://maps.googleapis.com/maps/api/staticmap?center=%lat%,%lng%&zoom=%zoom%&size=%width%x%height%&markers=color:red%7C%lat%,%lng%&sensor=false',
        readOnly: false,
        geocoder: null,
        map: null,
        marker: null,

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
                    script.src = "http://www.google.com/jsapi?sensor=false&language=es";
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
                
                var img = this.options.cache.context.find('img.mapImg');
                
                this.imgUrl = this.imgUrl.replace(/%lat%/g, this.options.cache.lat.val());
                this.imgUrl = this.imgUrl.replace(/%lng%/g, this.options.cache.lng.val());
                this.imgUrl = this.imgUrl.replace(/%zoom%/g, this.options.zoom);
                this.imgUrl = this.imgUrl.replace(/%width%/g, this.options.width);
                this.imgUrl = this.imgUrl.replace(/%height%/g, this.options.height);
                
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
                });
    
                google.maps.event.addListener(this.marker, 'dragend', function() {
    
                    self._geocodePosition(self.marker.getPosition());
                    self.options.cache.adress.trigger('change');
                });
            }
        },

        _bindEvents: function() {

            var self = this;

            this.options.cache.context.find("input[type=button]").click(function () {

                $(this).blur();
                self._geocode(self.options.cache.dummy.val());
            });

            this.options.cache.adress.on('change', function(){
                self._updateMarkerAddress($(this).val());
            });
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
