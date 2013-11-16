/**
 * Holds classes to work with modal/new windows
 * @type {object|{}}
 */
sky.windows = sky.windows || {};

$.extend(sky.windows, {

	/**
	 * Creates new modal window
	 * @param {object|string}	what Object to show in window or ajax url
	 * @param {object}			[data] Data to send with request
	 * @param {object|string}	[lock] Object or selector objects to be locked until end
	 * @type {{ holder: jQuery }}
	 */
	Modal: function(what, data, lock) {


		/* Self construct */
		if(!(this instanceof sky.windows.Modal))
			return new sky.windows.Modal(what, data, lock);


		/* Back link */
		var self = this;


		/* Add handler to window resize */
		$(window).on("resize.Modal scroll.Modal", { self: this }, this.rePosition);


		/* Create objects */
		this.background 	= $("<div/>").addClass(this.classes.background);
		this.holder			= $('<div/>').css("float", "left");
		this.dataContainer 	= $("<div/>").addClass(this.classes.window).append(this.holder).appendTo(this.background).on({
									mouseenter: function() { $(window).off("click.Modal"); },
									mouseleave: function() { $(window).on("click.Modal", function() { self.close() }); }
								});


		/* Callbacks */
		this.Callbacks = new sky.Callbacks();


		/* If we insert jQuery object */
		if(what instanceof jQuery) {

			this.insert(what);
			
		/* If we need to get url */
		} else {

			/* Perform ajax */
			this.ajax = sky.ajax(what, data, false, lock)
				.success(function(response) {
					self.insert(response.text);
				}).error(function(error, code) {
					self.Callbacks.onError(error, code);
				}).notSuccess(function() {
					self.Callbacks.onNotSuccess();
				}).always(function() {
					self.Callbacks.onAlways();
				}).abort(function() {
					self.Callbacks.onAbort();
				});
		}
		
		return this;
	
	},

	/* Opens new window */
	page: function(what, data, width, height, callBack) {
		
		/* Set window options */
		var windowOptions = 'height='+height+",width="+width+",left="+(screen.width - width)/2+",top="+(screen.height - height)/2+",status=no,location=no,toolbar=no,directories=no,menubar=no,scrollbars=yes";
		
		/* Set address */
		what = "https://" + window.location.host + "/" + what;
		
		/* Add request parameters */
		if(data) what += "?" + sky.hashHistory.makeFromObject(data);
		
		/* Create new window */
	   	var pw = window.open(what,"",windowOptions);
		
		/* Add callback function */
		if(callBack) $(pw).load(function() { callBack(pw.document); });
		
		/* Return new window */
		return pw;
		
	}

});

/**
 * Modal window prototype
 */
$.extend(sky.windows.Modal.prototype, {

	/**
	 * Holds class names for elements
	 */
	classes: {
		window: "modalWindow",
		background: "modalWindowDarkness"
	},

	/**
	 * Closes current window
	 * @param {boolean} byUser Indicates that window was closed not by user
	 */
	close: function(byUser) {

		/* Save scroll position because to may reset */
		var topOffset = $(window).scrollTop();

		/* Make body scrollable */
		$("body").css('overflow', '');

		/* Restore position */
		$(window).scrollTop(topOffset).off("resize.Modal scroll.Modal");

		/* Remove elements */
		this.background.fadeOut("fast", function() { $(this).remove() });

		/* Call close callback */
		this.Callbacks.onClose(!!byUser);

	},

	/**
	 * Resize action
	 * @param {jQuery.Event} event eventObject
	 */
	rePosition: function(event) {

		/* Get dimensions */
		//noinspection JSUnresolvedVariable
		var self 	  = event.data.self,
			jWindow	  = $(window),
			jBody	  = $("body"),
			topOffset = jWindow.scrollTop() > jBody.scrollTop() ? jWindow.scrollTop() : jBody.scrollTop();

		/* Min values set because of this field document may be bigger */
		self.background.css({ height: jWindow.height(), width: jWindow.width(), top: topOffset });

		/* Set dimensions and position */
		self.dataContainer.css({ height: "auto", width: "auto" })
			.width(self.holder.outerWidth())
			.center(self.background, true);

	},

	/**
	 * Data insertion and size correction
	 * @param what What to insert in to window
	 */
	insert: function(what) {

		/* Back link */
		var self = this;

		/* Append */
		this.holder.append(what);

		/* Add darkness */
		this.background.trigger("mouseleave").appendTo('body').css({opacity: 0, visibility: "visible"});

		/* Save scroll position because to may reset */
		var topOffset = $(window).scrollTop();

		/* Make body scrollable */
		$("body").css('overflow', 'hidden');

		/* Restore position */
		$(window).scrollTop(topOffset).off("resize.Modal scroll.Modal");

		/* After insert funtion */
		setTimeout(function() {
			self.background.animate({opacity: 1}, 300);
			self.Callbacks.onSuccess(self.holder, self);
			$(window).trigger("resize");
		}, 10);

	}
});