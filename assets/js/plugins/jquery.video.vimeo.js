/**
 * =====================================================
 *  Based on jQTubeUtil - jQuery YouTube Search Utility
 * =====================================================
 */

;jQVimeoUtil = (function($){ /* singleton */

    var f = function(){};
    var p = f.prototype;

    // Constants, Private Scope
    var MaxResults = 10,
        StartPoint = 1,
        // URLs
        BaseURL = "http://vimeo.com/api/v2",
        FeedsURL = BaseURL + '/',
        VideoURL = BaseURL + "/video/";

    /** Get a particular video via VideoID */
    p.video = function(vid, cb){
        return _request(VideoURL+ vid +".json", cb);
    };

    /** Get a particular video via VideoID */
    p.feed = function(channel, cb){

        url = FeedsURL+ channel +'/videos.json';
        return _request(url, cb);
    };

    /**
     * This method makes the actual JSON request
     * and builds the results that are returned to
     * the callback
     */
    function _request(url, callback){

        var res = {};
        $.ajax({
            type: "GET",
            dataType: "jsonp",
            url: url,
            async: true,
            success: function(xhr){

                if((typeof(xhr) == "undefined")
                    ||(xhr == null)) return;

                var videos = [];

                for(entry in xhr) {
                    videos.push(new VimeoVideo(xhr[entry]));
                }

                res.searchURL = url;
                res.videos = xhr;

                if(typeof(callback) == "function") {
                    callback(res); // pass the response obj
                    return;
                }

            },
            error: function(e){

                throw Exception("couldn't fetch Vimeo request : "+url+" : "+e);
            }
        });
        return res;
    };

    /**
     * Represents the object that transposes the
     * Vimeo video entry from the JSON response
     * into a usable object
     */
    var VimeoVideo = function(entry) {

        entry.videoId = entry.id;

        var unavail = [];
        var id = entry.id;


        // set values
        this.entry = entry;
        this.title = entry.title;
        try{ this.updated = entry.upload_date; } catch(e) { unavail.push("upload_date"); }
        try{ this.thumbs.small = entry.thumbnail_small; }catch(e){ unavail.push("thumbnail_small"); }
        try{ this.thumbs.medium = entry.thumbnail_medium; }catch(e){ unavail.push("thumbnail_medium"); }
        try{ this.thumbs.large = entry.thumbnail_large; }catch(e){ unavail.push("thumbnail_large"); }
        try{ this.duration = entry.duration; }catch(e){ unavail.push("duration"); }
        try{ this.favCount = entry.stats_number_of_likes; }catch(e){ unavail.push("stats_number_of_likes"); }
        try{ this.viewCount = entry.stats_number_of_plays; }catch(e){ unavail.push("stats_number_of_plays"); }
        try{ this.description = entry.description; }catch(e){ unavail.push("description"); }
        try{ this.keywords = entry.tags; }catch(e){ unavail.push("tags"); }
        this.unavailAttributes = unavail; // so that the user can tell if a value isnt available

    };

    return new f();

})(jQuery);