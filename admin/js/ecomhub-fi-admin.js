var ecombhub_fi_ajax_req = null; //active ajax request

(function ($) {

    ecomhub_fi_talk_to_backend('stats', {}, options_success);

    function options_success(d) {

        $('div.stats-html-here').html(d.html);

        ecomhub_fi_talk_to_backend('x_posts', {}, options_success);

        function options_success(d) {

            $('div.product-ids-html-here').html(d.html);
        }
    }




    function EcomhubFiRemoteModel() {
        // private
        var PAGESIZE = 50;
        var search_options = {
            sort_by: 'created_at_ts',
            sort_direction: 1,
            search_column: null,
            start_index: 0,
            limit: PAGESIZE
        };
        var data = {length: 0};
        var h_request = null; //timeout handle

        // events
        var onDataLoading = new Slick.Event();
        var onDataLoaded = new Slick.Event();


        function init() {
        }


        function isDataLoaded(from, to) {
            for (var i = from; i <= to; i++) {
                // noinspection EqualityComparisonWithCoercionJS
                if (data[i] == undefined || data[i] == null) {
                    return false;
                }
            }

            return true;
        }


        function clear() {
            for (var key in data) {
                delete data[key];
            }
            data.length = 0;
        }


        function ensureData(from, to) {
            if (ecombhub_fi_ajax_req && ecombhub_fi_ajax_req.toPage) {
                ecombhub_fi_ajax_req.abort();
                for (var i = ecombhub_fi_ajax_req.fromPage; i <= ecombhub_fi_ajax_req.toPage; i++)
                    data[i * PAGESIZE] = undefined;
            }

            if (from < 0) {
                from = 0;
            }

            if (data.length > 0) {
                to = Math.min(to, data.length - 1);
            }

            var fromPage = Math.floor(from / PAGESIZE);
            var toPage = Math.floor(to / PAGESIZE);

            while (data[fromPage * PAGESIZE] !== undefined && fromPage < toPage)
                fromPage++;

            while (data[toPage * PAGESIZE] !== undefined && fromPage < toPage)
                toPage--;

            //&start=" + (fromPage * PAGESIZE) + "&limit=" + (((toPage - fromPage) * PAGESIZE) + PAGESIZE
            search_options.start_index = fromPage * PAGESIZE;
            search_options.limit =  ((toPage - fromPage) * PAGESIZE) + PAGESIZE;
            // noinspection EqualityComparisonWithCoercionJS
            if (fromPage > toPage || ((fromPage == toPage) && data[fromPage * PAGESIZE] !== undefined)) {
                // TO DO:  look-ahead
                onDataLoaded.notify({from: from, to: to});
                return;
            }


            if (h_request != null) {
                clearTimeout(h_request);
            }

            h_request = setTimeout(function () {
                for (var i = fromPage; i <= toPage; i++)
                    data[i * PAGESIZE] = null; // null indicates a 'requested but not available yet'

                onDataLoading.notify({from: from, to: to});


                ecomhub_fi_talk_to_backend('list', search_options, onSuccess);

                ecombhub_fi_ajax_req.fromPage = fromPage;
                ecombhub_fi_ajax_req.toPage = toPage;
            }, 50);
        }


        function onSuccess(resp) {
            var from = resp.meta.start_index, to = from + resp.meta.limit;
            data.length = Math.min(resp.results.length, 1000); // limitation of the API

            for (var i = 0; i < resp.results.length; i++) {
                // noinspection UnnecessaryLocalVariableJS
                var item = resp.results[i];

                data[from + i] = item;
                data[from + i].index = from + i;
            }


            onDataLoaded.notify({from: from, to: to});
        }


        function reloadData(from, to) {
            for (var i = from; i <= to; i++)
                delete data[i];

            ensureData(from, to);
        }


        function setSort(column, dir) {
            search_options.sort_by = column;
            search_options.sort_direction = dir;
            clear();
        }

        function setSearch(column,str) {
            search_options.search_value = str;
            search_options.search_column =  column;
            clear();
        }


        init();

        return {
            // properties
            "data": data,

            // methods
            "clear": clear,
            "isDataLoaded": isDataLoaded,
            "ensureData": ensureData,
            "reloadData": reloadData,
            "setSort": setSort,
            "setSearch": setSearch,

            // events
            "onDataLoading": onDataLoading,
            "onDataLoaded": onDataLoaded
        };
    }

    // Slick.Data.EcomhubFiRemoteModel
    $.extend(true, window, {Slick: {Data: {RemoteModel: EcomhubFiRemoteModel}}});
    console.log("extension loaded");

})(jQuery);


function ecomhub_fi_bind_post_to_clickfunnels(post_id,product_id) {

        ecomhub_fi_talk_to_backend('x_posts', {bind: post_id,product_id: product_id}, options_success);

        function options_success(d) {

            jQuery('div.product-ids-html-here').html(d.html);
        };
}

function ecomhub_fi_talk_to_backend(method, server_options, success_callback, error_callback) {

    if (!server_options) {
        server_options = {};
    }

    // noinspection ES6ModulesDependencies
    var outvars = jQuery.extend({}, server_options);
    // noinspection JSUnresolvedVariable
    outvars._ajax_nonce = ecomhub_fi_backend_ajax_obj.nonce;
    // noinspection JSUnresolvedVariable
    outvars.action = ecomhub_fi_backend_ajax_obj.action;
    outvars.method = method;
    // noinspection ES6ModulesDependencies
    // noinspection JSUnresolvedVariable
    ecombhub_fi_ajax_req = jQuery.ajax({
        type: 'POST',
        beforeSend: function () {
            if (ecombhub_fi_ajax_req && (ecombhub_fi_ajax_req !== 'ToCancelPrevReq') && (ecombhub_fi_ajax_req.readyState < 4)) {
            //    ecombhub_fi_ajax_req.abort();
            }
        },
        dataType: "json",
        url: ecomhub_fi_backend_ajax_obj.ajax_url,
        data: outvars,
        success: success_handler,
        error: error_handler
    });

    function success_handler(data) {

        // noinspection JSUnresolvedVariable
        if (data.is_valid) {
            if (success_callback) {
                success_callback(data.data);
            } else {
                console.debug(data);
            }
        } else {
            if (error_callback) {
                error_callback(data.data);
            } else {
                console.debug(data);
            }

        }
    }

    /**
     *
     * @param {XMLHttpRequest} jqXHR
     * @param {Object} jqXHR.responseJSON
     * @param {string} textStatus
     * @param {string} errorThrown
     */
    function error_handler(jqXHR, textStatus, errorThrown) {
        if (errorThrown === 'abort' || errorThrown === 'undefined') return;
        var what = '';
        var message = '';
        if (jqXHR && jqXHR.responseText) {
            try {
                what = jQuery.parseJSON(jqXHR.responseText);
                if (what !== null && typeof what === 'object') {
                    if (what.hasOwnProperty('message')) {
                        message = what.message;
                    } else {
                        message = jqXHR.responseText;
                    }
                }
            } catch (err) {
                message = jqXHR.responseText;
            }
        } else {
            message = "textStatus";
            console.info('Admin Ecomhub ajax failed but did not return json information, check below for details', what);
            console.error(jqXHR, textStatus, errorThrown);
        }

        if (error_callback) {
            error_callback(message);
        } else {
            console.warn(message);
        }


    }
}




