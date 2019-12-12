$(document).ready(function () {

    !function () { function a(a, b) { var c = void 0 !== window.pageYOffset ? window.pageYOffset : (document.documentElement || document.body.parentNode || document.body).scrollTop, d = document.documentElement.clientHeight, e = c + d; b = b || 0; var f = a.getBoundingClientRect(); if (0 === f.height) return !1; var g = f.top + c - b, h = f.bottom + c + b; return h > c && e > g } jQuery.expr[":"]["near-viewport"] = function (b, c, d) { var e = parseInt(d[3]) || 0; return a(b, e) } }();

    $.ajaxSetup({ cache: false });

    var videoFormat = 'webm';
    var v = document.createElement('video');
    if (v.canPlayType && v.canPlayType('video/mp4').replace(/no/, '')) {
        videoFormat = 'mp4';
    }

    function renderEsObject(esObject, wrapper) {

        esObject.wrap('<span style="' + esObject.attr('style') + '"></span>');

        var url = 'index.php?eID=edusharing_proxy';
        url += '&videoFormat=' + videoFormat;
        url += '&edusharing_width=' + parseInt(esObject.css('width'));
        url += '&edusharing_height=' + parseInt(esObject.css('height'));
        url += '&cache=' + $.now();

        $.each(esObject[0].attributes, function () {
            if (this.specified) {
                url += '&' + this.name + '=' + this.value;
            }
        });

        if (typeof wrapper == 'undefined')
            var wrapper = esObject.parent();

        $.get(url, function (data) {
            wrapper.html('').append(data);
            if (data.toLowerCase().indexOf('data-view="lock"') >= 0)
                setTimeout(function () { renderEsObject(esObject, wrapper); }, 1111);
        });

        wrapper.css({ width: 'auto', height: 'auto' });
    }

    $("[data-edusharing_uid]").each(function () {
        renderEsObject($(this));
    })

    // Why?
    // $(window).scroll(function() {
    //     $("[data-edusharing_uid]").each(function() {
    //         renderEsObject($(this));
    //     })
    // });

    $("body").click(function (e) {
        if ($(e.target).closest(".edusharing_metadata").length) {
            //clicked inside ".edusharing_metadata" - do nothing
        } else {
            $(".edusharing_metadata_toggle_button").text($(".edusharing_metadata_toggle_button").data('textopen'));
            $(".edusharing_metadata").hide();
            if ($(e.target).closest(".edusharing_metadata_toggle_button").length) {
                $(".edusharing_metadata_toggle_button").text($(this).data('textopen'));
                $(".edusharing_metadata").hide();
                toggle_button = $(e.target);
                metadata = toggle_button.parent().find(".edusharing_metadata");
                if (metadata.hasClass('open')) {
                    metadata.toggleClass('open');
                    metadata.hide();
                    toggle_button.text(toggle_button.data('textopen'));
                } else {
                    $(".edusharing_metadata").removeClass('open');
                    metadata.toggleClass('open');
                    metadata.show();
                    toggle_button.text(toggle_button.data('textclose'));
                }
            } else {
                $(".edusharing_metadata").removeClass('open');
            }
        }
    });
})
