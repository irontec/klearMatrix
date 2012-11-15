// Info::
//  http://www.tikku.com/jquery-jqtube-util#search_example_1

(function load($) {

    if (!$.klear.checkDeps(['window.jQTubeUtil'],load)) {
        return;
    }

    $.widget("ui.video", $.klearmatrix.module, {
        widgetEventPrefix:"video",

        options: {
            cache: {}
        },
        _setOption: function (name, value) {

            $.Widget.prototype._setOption.apply(this, arguments);
        },
        _create: function() {

            var context = this.element.klearModule("getPanel");

            //div.containner de klear
            this.options.cache.wrapper = context.parent();
            //input type text donde introduccir la url del vídeo
            this.options.cache.dummy = context;

            this.options.cache.id = context.parent().find("input:eq(1)");
            this.options.cache.source = this.options.cache.id.next("");
            this.options.cache.title = this.options.cache.source.next();
            this.options.cache.thumb = this.options.cache.title.next();


            this._bindEvents();

            switch (this.options.cache.source.val()) {

                case 'youtube':
                case 'vimeo':

                    this._render();
                    break;
            }

            this._initFeeds();
        },
        _bindEvents: function() {

            var self = this;
            this.options.cache.dummy.on("focusout", function () {

               self._cleanUrl($(this));
            });

            this.options.cache.wrapper.find("li.prev").bind("click", function (e) {

               e.preventDefault();
               e.stopPropagation();

               $(this).children("a").blur();
               self._showPrev($(this));
            });

            this.options.cache.wrapper.find("li.next").bind("click", function (e) {

               e.preventDefault();
               e.stopPropagation();

               $(this).children("a").blur();
               self._showNext($(this));
            });
        },

        _initFeeds: function () {

            var self = this;
            $("div.feed").each(function () {

                if ($(this).data("channel")) {

                    var currentFeed = this;

                    if ($(this).data("source") == 'youtube') {

                        jQTubeUtil.feed($(this).data("channel"), function (results) {
                            if (results) {

                                self._renderFeed(currentFeed, results, "youtube");
                            }
                        });

                    } else if ($(this).data("source") == 'vimeo') {

                        jQVimeoUtil.feed($(this).data("channel"), function (results) {
                            if (results) {

                                self._renderFeed(currentFeed, results, "vimeo");
                            }
                        });
                    }
                }
            });
        },

        _renderFeed: function (containner, results, source) {

            source = source || "";
            var vid, url, title, author, src;

            var self = this;
            var results = results || {};

            switch(source) {

                case 'youtube':

                    author = results.videos[0].entry.author[0].name.$t;
                    break;

                case 'vimeo':

                    var userUrl = results.videos[0].user_url.split("/");
                    author = userUrl[userUrl.length -1];
                    break;

                default:

                    throw ("Unknown source " + source);
            }

            for (idx in results.videos) {

                switch(source) {

                    case 'youtube':

                        vid = results.videos[idx].videoId;

                        var linkNum = results.videos[idx].entry.link.length;
                        url = results.videos[idx].entry.link[linkNum-2].href;
                        title = results.videos[idx].title;
                        src = 'http://i.ytimg.com/vi/'+ vid +'/2.jpg';
                        break;

                    case 'vimeo':

                        id = results.videos[idx].id;
                        url = results.videos[idx].url;
                        title = results.videos[idx].title;
                        src = results.videos[idx].thumbnail_small;
                        break;
                }

                var $img = $("<img />").attr("src", src);
                $img.css("cursor", "pointer").attr("data-url", url);
                $img.attr("alt",  title);
                $img.attr("title",  title);
                $(containner).find("div.content").append($img);

                $img.bind("click", function () {

                    self._selectVideo($(this));
                });
            }

            if (!this.options.cache.feedContainner) {

                this.options.cache.feedContainner = {};
            }

            this.options.cache.feedContainner[author] = this.options.cache.wrapper.find("div.feed[data-channel="+author+"] div.content");

            var itemNum = this.options.cache.feedContainner[author].children("img").length;
            this.options.cache.feedContainner[author].data("itemNum", itemNum);

            this.options.cache.feedContainner[author].data("offset", 0);

            if (itemNum * $img.width() > this.options.cache.feedContainner[author].width()) {

                this.options.cache.feedContainner[author].parent().css("width", this.options.cache.feedContainner[author].width());
                this.options.cache.feedContainner[author].css("width", itemNum * $img.width());
                this.options.cache.feedContainner[author].parents("div.feed").find("li.prev, li.next").show();
            }
        },

        _selectVideo: function (video) {

            this.options.cache.dummy.val(video.data("url"));
            this.options.cache.dummy.trigger("focusout");
        },

        _showNext: function (element) {
            var channel = element.parents("div").data("channel");

            var currentOffset = this.options.cache.feedContainner[channel].data("offset");
            if (Math.abs(currentOffset) >= this.options.cache.feedContainner[channel].data("itemNum") -1) {

                return;
            }

            var currentOffset = this.options.cache.feedContainner[channel].data("offset");
            if (currentOffset == this.options.cache.feedContainner[channel].data("itemNum")) {

                return;
            }

            this.options.cache.feedContainner[channel].data("offset", currentOffset - 1);
            this.options.cache.feedContainner[channel].stop().animate({
                "left" : this.options.cache.feedContainner[channel].data("offset")
                         * this.options.cache.feedContainner[channel].find("img:eq(0)").width()
            });
        },

        _showPrev: function (element) {
            var channel = element.parents("div").data("channel");

            var currentOffset = this.options.cache.feedContainner[channel].data("offset");
            if (currentOffset == 0) {

                return;
            }

            this.options.cache.feedContainner[channel].data("offset", currentOffset + 1);
            this.options.cache.feedContainner[channel].stop().animate({
                "left" : this.options.cache.feedContainner[channel].data("offset")
                         * this.options.cache.feedContainner[channel].find("img:eq(0)").width()
            });
        },

        _cleanUrl: function (element) {

            var youtubeRegExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?.*\&?v=)([^#\&\?]*).*/;
            var vimeoRegExp = /^http:\/\/(www\.)?vimeo\.com\/(clip\:)?(\d+).*$/;

            var match = element.val().match(youtubeRegExp);
            var vimeoMatch = element.val().match(vimeoRegExp);

            if (match != null && match.length > 0) {

                 match = match[match.length -1];
                 this.options.cache.source.val("youtube");

                 element.val("http://youtu.be/" + match);
                 element.trigger("change");

                 var self = this;
                 jQTubeUtil.video(match, function(response){

                    self.options.cache.id.val(response.videos[0].videoId);
                    self.options.cache.title.val(response.videos[0].title);
                    self.options.cache.thumb.val(response.videos[0].thumbs[0].url);
                    self._render.call(self);
                });

            } else if (vimeoMatch != null && vimeoMatch.length > 0) {

                 vimeoMatch = vimeoMatch[vimeoMatch.length -1];
                 this.options.cache.source.val("vimeo");

                 element.trigger("change");

                 var self = this;
                 jQVimeoUtil.video(vimeoMatch, function(response) {

                    self.options.cache.id.val(response.videos[0].id);
                    self.options.cache.title.val(response.videos[0].title);

                    self.options.cache.thumb.val(response.videos[0].thumbnail_large);
                    self._render.call(self);
                });
            }
        },

        _render: function () {

             var self = this;

             if (this.options.cache.wrapper.children("img").length > 0
                 && this.options.cache.id.val() == this.options.cache.wrapper.children("img:eq(0)").attr("id")
             ) {

                 return;
             }

             var title = $("<p />");
             title.html(this.options.cache.title.val());
             this.options.cache.wrapper.children("p").remove();
             this.options.cache.wrapper.append(title);

             var thumb = $("<img />").attr("src", this.options.cache.thumb.val());
             thumb.attr("id", this.options.cache.id.val());

             thumb.css("cursor", "pointer");
             thumb.bind("click", function () {

                self._showPlayer();
             });

             this.options.cache.wrapper.children("img").remove();
             this.options.cache.wrapper.children("iframe").remove();
             this.options.cache.wrapper.append(thumb);
        },

        _showPlayer: function () {

            switch (this.options.cache.source.val()) {

                case 'youtube':

                    var code = '<iframe width="560" height="315" src="http://www.youtube.com/embed/' + this.options.cache.id.val() + '?rel=0&autoplay=1" frameborder="0" allowfullscreen></iframe>';
                    break;

                case 'vimeo':

                    var code = '<iframe src="http://player.vimeo.com/video/' + this.options.cache.id.val() + '?byline=0&amp;portrait=0&amp;badge=0&amp;color=ffffff" width="640" height="360" \
                               frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>';

                    break;

            }

            // remove classes + data
            this.options.cache.wrapper.children("img").remove();
            this.options.cache.wrapper.children("iframe").remove();
            this.options.cache.wrapper.append(code)
        },

        destroy: function() {

            // remove classes + data
            $.Widget.prototype.destroy.call( this );
            return this;
        },
    });

    $.widget.bridge("ui.video");

})( jQuery );
