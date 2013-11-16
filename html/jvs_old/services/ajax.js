
/* Create module */
angular.module("skyApp")
	.service("ajax", ["$http", function($http) {

		/**
		 * Advanced ajax execution
		 * To abort call stop() method instead of abort() because it's used for abort callback set, or .ajax.abort()
		 * You may skip type, don't skip it if lock is string set false in object and data
		 * @param {string} url				Requested url
		 * @param {object} [data]			Holds request parameters
		 * @param {jQuery} [object]			Object which performs request, to disable it during request
		 * @param {object} [options]		Additional ajax options, see http://api.jquery.com/jQuery.ajaxSetup/
		 * @param {object} [callbackData]	Additional params that passed to any callback
		 */
		return function(url, data, object, options, callbackData) {

			/* Parameters shifting */
			if(!options && object && !(object instanceof String) && !(object instanceof jQuery)) {
				callbackData = options;
				options 	 = object;
				object 		 = undefined;
			}

			/* Add base */
			if(page.data.base)
				url = page.data.base + "/" + url;

			/* If no options */
			if(!options)
				options = {};

			/* Lock button */
			if(object)
				object = $(object).filter(":not(:disabled):not(.disabled)").disable();

			/* New object to store callbacks */
			var callbacks = new sky.Callbacks(["success", "error", "notSuccess", "abort", "notAbort", "always"]);
			callbacks.stop = function() { this.ajax.abort(); };

			/* Perform ajax request */
			callbacks.ajax = $http(jQuery.extend(true, {

				/* Set base options */
				url     	: url,
				data    	: data,
				dataType	: "json",
				type    	: "post",

				/**
				 * Function executed on success
				 * @param {object|string} response 	Response object or string
				 * @param {string} [response.text] 	Response error text
				 * @param {string} [response.error] Response error flag
				 * @param {Object} jqXHR        Request object
				 * @param {String} textStatus   Status text
				 * @returns {*}
				 */
				success: function(response, textStatus, jqXHR) {

					/* Unlock objects */
					if(object)
						object.enable();

					/* Possible params list */
					var params = $.extend({ jqXHR: jqXHR, textStatus: textStatus, object: object }, callbackData);

					/* If empty response */
					if(response == null) {
						params.error = "Данные небыли переданы";
						params.type = "noData";
					}

					/* If response returned with error */
					if(response.error) {
						params.error = response.text;
						params.type = "php";
					}

					/* If error type set */
					if(params.type)
						return callbacks.fire("notSuccess, error", params); // No data

					/* Set response in possible params */
					params.response = params.data = response;

					/* User success function */
					return callbacks.fire("success, notAbort", params);

				},

				/**
				 * Function executed on error
				 * @param {Object} jqXHR        Request object
				 * @param {String} textStatus   Status text
				 * @param {String} errorThrown  Http error text
				 */
				error: function(jqXHR, textStatus, errorThrown) {


					/* Unlock objects */
					if(object)
						object.enable();

					console.log(jqXHR);

					/* Defaults */
					var type        = "Unknown",
						errorText   = "Во время выполнения запроса произошла ошибка, пожалуйста попробуйте позже";

					/* Get error text according to response data */
					if(textStatus == "abort") {
						type 	  = "abort";
						errorText = 'Выполнение запроса прервано';
					} else if(textStatus == 'parsererror') {
						type 	  = "parse";
						errorText = 'Ответ пришел в неверном формате, пожалуйста попробуйте позже'; // + jqXHR.responseText;
					} else if(textStatus == 'timeout') {
						type 	  = "timeout";
						errorText = 'Время ожидания ответа истекло';
					} else if(jqXHR.status == 0) {
						type 	  = "stopped";
						errorText = 'Загрузка остановлена, проверьте свои настройки сети';
					} else if(sky.ajax.codes[jqXHR.status]) {
						type = jqXHR.status;
						errorText = 'Ошибка во времы выполнения запроса (' + sky.ajax.codes[jqXHR.status] + ')';
					}

					/* Possible params list */
					var params = jQuery.extend({
						error      : errorText,
						type       : type,
						code	   : type,
						jqXHR      : jqXHR,
						textStatus : textStatus,
						status     : textStatus,
						errorThrown: errorThrown,
						object     : object
					}, callbackData);

					/* Execute callback */
					callbacks.fire("notSuccess", params);

					/* Execute special callbacks */
					callbacks.fire(textStatus == "abort" ? "abort" : "error, notAbort", params);

				}

			}, options));

			console.log(callbacks.ajax.promise);

			/* On always set */
			///callbacks.ajax.promise.always(function(jqXHR, textStatus, errorThrown) {
			//	//noinspection JSUnresolvedFunction
			//	callbacks.aOnAlways({ errorThrown: errorThrown, textStatus: textStatus, jqXHR: jqXHR, object: object});
			//});

			return callbacks;

		}
	}]);