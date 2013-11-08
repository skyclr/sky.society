/**
 * Sends file data via HttpRequest
 * @param options
 */
sky.AjaxFiles.XHR = function(options) {

	/* Save options */
	$.extend(this, {
		options     : options,
		files       : options.files,
		input       : options.input,
		url         : options.url,
		data        : options.data,
		callbacks   : options.callbacks,
		toProceed   : options.files.length,
		inProgress  : 0,
		totalLoaded : 0,
		totalSize	: 0,
		totalPercent: 0,
		current		: 0,
		fileRequests: {}
	});

	/* Back link */
	var self = this;

	/* Go through */
	$.each(options.files, function(id) {
		self.totalSize += self.getSize(id);
	});
};

/* Prototype functions */
jQuery.extend(sky.AjaxFiles.XHR.prototype, {

	/**
	 * Get file name
	 * @param {string} id File name in files stack
	 * @returns {string}
	 */
	getName: function(id) {
		return this.files[id].name.replace(/.*(\/|\\)/, "");
	},

	/**
	 * Get file size
	 * @param {string} id File name in files stack
	 * @returns {int}
	 */
	getSize: function(id) {
		return this.files[id].size;
	},

	/**
	 * Uploads file
	 * @param {string} id File name in files stack
	 */
	send: function(id) {

		/* Create elements */
		var fileData = this.files[id],
			self = this,
			file = { // This obj will store data associated with XHR
				id     : Math.random(),
				name   : this.getName(id),
				size   : this.getSize(id),
				ajax   : false,
				percent: false,
				loaded : 0
			};

		/* Prepare params */
		var params = this.data || {};

		/* Build string */
		var queryString = this.url + "?ajaxFile=" + file.name + "&" + jQuery.param(params);

		/**
		 * Params extend
		 * @param {object} args Object to be extended
		 * @returns {*}
		 */
		this.extend = function(args) {
			return jQuery.extend(args, {
				totalLoaded : self.totalLoaded,
				totalSize   : self.totalSize,
				totalPercent: self.totalPercent,
				file        : file,
				loaded      : file.loaded,
				size        : file.size,
				percent     : file.percent,
				toProceed	: self.toProceed,
				current		: self.current
			});
		};

		/* Send */
		if(sky.FormDataIsSupported) {

			/* Create form data sender */
			var form = new FormData();
			form.append(this.options.inputName, fileData);

			/* Send start */
			//noinspection JSUnusedGlobalSymbols
			file.ajax = sky.ajax(queryString, form, {
				processData: false,
				contentType: false,
				type: "POST",
				xhr: function() {
					try {

						var xhr = new XMLHttpRequest();

						/* Set special upload api handlers */
						xhr.upload["onloadstart"] = function() {
							self.inProgress++;
							self.callbacks.fire("begin", self.extend({}));
						};
						xhr.upload.onprogress = function(event) {
							self.totalLoaded += event.loaded - file.loaded;
							self.totalPercent = (self.totalLoaded / self.totalSize * 100).toFixed(0);
							self.onProgress(event, file);
						};

						return xhr;

					} catch( e ) { return undefined; }
				}
			});

		} else {

			/* Send start */
			file.ajax = sky.ajax(queryString, fileData, {
				processData: false,
				contentType: false,
				type: "POST",
				beforeSend: function(jqXHR) {
					jqXHR.setRequestHeader("X-Requested-With"	, "XMLHttpRequest");
					jqXHR.setRequestHeader("X-File-Name"		, encodeURI(file.name));
					jqXHR.setRequestHeader("Content-Type"		, "multipart/form-data");
					jqXHR.setRequestHeader("Content-Disposition", 'attachment; filename="' + encodeURI(file.name) + '"');
					jqXHR.setRequestHeader("Accept", "text/html,application/xhtml+xml,application/xml;q=0.9");
				}
			});

		}

		/* Set ajax callbacks */
		file.ajax
			.success(function(allPossibleArguments) {
				self.callbacks.fire("success", self.extend(allPossibleArguments));
			})
			.error(function(allPossibleArguments) {
				self.callbacks.fire("error", self.extend(allPossibleArguments));
			})
			.notSuccess(function(allPossibleArguments) {
				self.callbacks.fire("notSuccess", self.extend(allPossibleArguments));
			})
			.always(function(allPossibleArguments) {

				self.inProgress--;
				self.toProceed--;

				/* Call always method */
				self.callbacks.fire("always", self.extend(allPossibleArguments));

				/* Delete connection */
				delete self.fileRequests[file.id];

			});

		/* Save */
		this.fileRequests[file.id] = file;


	},

	/**
	 * Fores on progress change
	 * @param event
	 * @param fileRequestData
	 */
	onProgress: function(event, fileRequestData) {

		/* Count percentage */
		var percent = (event.loaded / event["total"] * 100).toFixed(0);
		fileRequestData.loaded = event.loaded;

		/* If percent changed */
		if(percent !== fileRequestData.percent && event["lengthComputable"]) {
			fileRequestData.percent = percent;
			this.callbacks.fire("progress", this.extend({}));
		}

	},

	/**
	 * Aborts current download
	 */
	abort: function() {

		/* Stop each request */
		$.each(this.fileRequests, function() {
			//noinspection JSPotentiallyInvalidUsageOfThis
			this.ajax.stop();
		});

	}
});