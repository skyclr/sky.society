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
sky.ajax = function(url, data, object, options, callbackData) {

	/* Parameters shifting */
	if(!options && object && !(object instanceof String) && !(object instanceof jQuery)) {
		callbackData = options;
		options 	 = object;
		object 		 = undefined;
	}

	if(url.charAt(0) == "/")
		url = url.substr(1);

	/* Add base */
	if(page.data.base)
		url = page.data.base + url;



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
	callbacks.ajax = $.ajax($.extend(true, {

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

	/* On always set */
	callbacks.ajax.promise().always(function(jqXHR, textStatus, errorThrown) {
		//noinspection JSUnresolvedFunction
		callbacks.aOnAlways({ errorThrown: errorThrown, textStatus: textStatus, jqXHR: jqXHR, object: object});
	});

	return callbacks;

};

/* Get is XHR is supported */
sky.XHRIsSupported 		= (typeof new XMLHttpRequest().upload !== "undefined");
sky.FormDataIsSupported = window.FormData && true;



/**
 * Class to work with dynamic file upload
 * @param {html|string} input Input to be used to upload
 * @param {string} url Url to upload
 * @param {object} [data] Data to be send with request
 * @param {sky.Callbacks|function} [callbacks] Callbacks to be called on events
 */
sky.AjaxFiles = function(input, url, data, callbacks) {

	/* Self cinstruct */
	if(!(this instanceof sky.AjaxFiles))
		return new sky.AjaxFiles(input, url, data, callbacks);

	/* Save items */
	this.input 		= $(input).get(0);
	this.$input		= $(input);
	this.inputName	= this.$input.attr("name");
	this.url   		= url;
	this.data  		= data;
	this.callbacks 	= callbacks ? callbacks : new sky.Callbacks(["success", "error", "notSuccess", "abort", "always", "progress", "begin"]);
	this.files		= [];

	/* Back link */
	var self = this;

	/**
	 * Function to handle file input change
	 */
	self.saveFiles = function() {

		/* Clear */
		self.files = [];

		/* Get files list */
		if(sky.XHRIsSupported) {

			/* Get files from event */
			//noinspection JSUnresolvedVariable
			var files = self.input.files;

			/* Save them to this */
			for(var i = 0; i < files.length; i++)
				self.files.push(files[i]);

		} else self.files.push(this);

	};

	/**
	 * Sends ajax files
	 */
	this.send = function(parallel) {

		/* Get files list */
		self.saveFiles();

		/* If no files */
		if(!self.files.length)
			return false;

		/* Create supported handler */
		var handler;
		if(sky.XHRIsSupported) 	handler = new sky.AjaxFiles.XHR(self);
		else 					handler = new sky.AjaxFiles.IFrame(self);

		/* Send files */
		if(parallel)
			self.sendParallel(handler);
		else
			self.sendСonsequentially(handler);

		/* return send handler */
		return handler;

	};

	/**
	 * Sends files consequentially
	 * @param handler
	 */
	this.sendСonsequentially = function(handler) {

		/* First id */
		var id = 0;

		/* Set sending next after this one */
		this.callbacks.on("always", function() {
			id++;
			if(self.files[id])
				handler.send(id);
		});

		/* Send first */
		handler.send(id);

	};

	/**
	 * Sends files parallel
	 * @param handler
	 */
	this.sendParallel = function(handler) {

		/* Send files through them */
		$.each(self.files, function(i) {
			handler.send(i);
		});

	};

	/* Self return */
	return this;

};

/**
 * Contains http codes
 */
sky.ajax.codes = {

	/* Request errors */
	400: "Неверный запрос",
	401: "Для выполнеия запроса нужня авторизация",
	402: "Для доступа к ресурсу необходима оплата",
	403: "Доступ к ресурсу запрещен",
	404: "Сервер для выполнения запроса не найден или запрос выполнялся слишком долго",
	405: "Не поддерживаемый метод HTTP",
	406: "Не приемлемо",
	407: "Необходима аутентификация прокси",
	408: "Истекло время ожидания",
	409: "Конфликт",
	410: "Ресурс удален",
	411: "В запросе не указана длинна",
	412: "Условие ложно",
	413: "Размер запроса слишком велик",
	414: "Запрашиваемый URI слишком длинный",
	415: "Неподдерживаемый тип данных",
	416: "Запрашиваемый диапазон не достижим",
	417: "Ожидаемое неприемлемо",
	422: "Необрабатываемый экземпляр",
	423: "Ресурс заблокировано",
	424: "Невыполненная зависимость",
	425: "Неупорядоченный набор",
	426: "Необходимо обновление",
	428: "Необходимо предусловие",
	429: "Слишком много запросов",
	431: "Поля заголовка запроса слишком большие",
	451: "Недоступно по юридическим причинам",
	456: "Некорректируемая ошибка",
	499: "Используется Nginx, когда клиент закрывает соединение до получения ответа",

	/* Server error */
	500: "Внутренняя ошибка сервера",
	501: "Не реализовано",
	502: "Похой, ошибочный шлюз",
	503: "Сервис недоступен",
	504: "Шлюз не отвечает",
	505: "Версия HTTP не поддерживается",
	506: "Вариант тоже проводит согласование",
	507: "Переполнение хранилища",
	508: "Запрос зациклен",
	509: "Исчерпана пропускная ширина канала",
	510: "Не расширено",
	511: "Требуется сетевая аутентификация"

};