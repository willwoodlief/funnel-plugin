jQuery(document).ready(function( $ ) {

    var ecombhub_fi_ajax_req = null; //active ajax request
    function ecomhub_fi_talk_to_frontend(method, server_options, success_callback, error_callback) {

        if (!server_options) {
            server_options = {};
        }

        // noinspection ES6ModulesDependencies
        var outvars = $.extend({}, server_options);
        // noinspection JSUnresolvedVariable
        outvars._ajax_nonce = ecombhub_fi_public_ajax_obj.nonce;
        // noinspection JSUnresolvedVariable
        outvars.action = ecombhub_fi_public_ajax_obj.action;
        outvars.method = method;
        // noinspection ES6ModulesDependencies
        // noinspection JSUnresolvedVariable
        ecombhub_fi_ajax_req = $.ajax({
            type: 'POST',
            beforeSend: function () {
                if (ecombhub_fi_ajax_req && (ecombhub_fi_ajax_req !== 'ToCancelPrevReq') && (ecombhub_fi_ajax_req.readyState < 4)) {
                    //    ecombhub_fi_ajax_req.abort();
                }
            },
            dataType: "json",
            url: ecombhub_fi_public_ajax_obj.ajax_url,
            data: outvars,
            success: success_handler,
            error: error_handler
        });

        function success_handler(data) {

            // noinspection JSUnresolvedVariable
            if (data.is_valid) {
                if (data.hasOwnProperty('new_nonce') ) {
                    ecombhub_fi_public_ajax_obj.nonce = data.new_nonce;
                }
                if (success_callback) {
                    success_callback(data);
                } else {
                    console.debug(data);
                }
            } else {
                if (error_callback) {
                    console.warn(data);
                    error_callback(null,data);
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
                    what = $.parseJSON(jqXHR.responseText);
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
                console.warn(message);
                error_callback(message,null);
            } else {
                console.warn(message);
            }


        }
    }


    // addEventListener support for IE8
    function bindEvent(element, eventName, eventHandler) {
        if (element.addEventListener) {
            element.addEventListener(eventName, eventHandler, false);
        } else if (element.attachEvent) {
            element.attachEvent('on' + eventName, eventHandler);
        }
    }

    // Send a message to the parent
    var sendMessageToParent = function (msg) {
        // Make sure you are sending a string, and to stringify JSON
        window.parent.postMessage(msg, '*');
    };



    // Listen to messages from parent window
    bindEvent(window, 'message', function (e) {
        var message_object = JSON.parse(e.data);
        var data_to_server =  message_object.data;
        var method = message_object.method;
        ecomhub_fi_talk_to_frontend(method,data_to_server,
            function(data_from_server) {
                console.log(data_from_server);
                var message = JSON.stringify(data_from_server);
                sendMessageToParent(message);
            },
            function(message,data) {
                // if the ajax is working, this will have data but not message
                // if the ajax is not working, this will have message but no data
                if (data) {
                    if (data.hasOwnProperty('for_user') && data.for_user) {
                        var message_to_parent = JSON.stringify({method: data.method, is_valid: false, message: data.message});
                        sendMessageToParent(message_to_parent);
                    }
                }
            }
        );
    });




});




