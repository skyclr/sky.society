/**
 * Holds classes to work with modal/new windows
 * @type {object|{}}
 */
sky.windows = sky.windows || { list: [] };

$.extend(sky.windows, {

	/**
	 * Creates new modal window
	 * @param {object|string}	name Window name
	 * @param {object}			[data] Data to send with request
	 * @param {object|string}	[lock] Object or selector objects to be locked until end
	 * @type {{ holder: jQuery }}
	 */
	Modal: function(name, data, lock) {


		/* Self construct */
		if(!(this instanceof sky.windows.Modal))
			return new sky.windows.Modal(name, data, lock);

		/* Create window */
		this.background 	= sky.templates.render("windows-modal", {}).appendTo("body").data("modalWindow", this);;
		this.dataContainer 	= this.background.children();
		this.holder 		= this.dataContainer.children();

		/* Callbacks */
		this.callbacks = new sky.Callbacks(["success", "error", "notSuccess", "always", "abort", "close"]);

		/* Link */
		this.template = sky.templates.render(name, data).appendTo(this.holder);

		/* Save scroll position because it may reset */
		var topOffset = $(window).scrollTop();

		/* Make body scrollable */
		$("body").css('overflow', 'hidden');

		/* Restore position */
		$(window).scrollTop(topOffset);

		/* Add to list */
		sky.windows.list.push(this);

		/* Resize to full */
		$(window).trigger("resize");

		/* Return */
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
		this.callbacks.fire("close", { byUser: !!byUser });

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

/* Add handler to window resize */
$(window)
	.on("resize.modal scroll.modal", function() {

		/* If no windows */
		if(!sky.windows["list"].length)
			return;

		/* Get sizes */
		var jWindow	  = $(window),
			jBody	  = $("body"),
			topOffset = jWindow.scrollTop() > jBody.scrollTop() ? jWindow.scrollTop() : jBody.scrollTop();

		/* Apply for windows */
		$.each(sky.windows.list, function(_, modal) {

			/* Min values set because of this field document may be bigger */
			modal.background.css({ height: jWindow.height(), width: jWindow.width(), top: topOffset });

			/* Set dimensions and position */
			modal.dataContainer.css({ height: "auto", width: "auto" });

			/* Set width */
			modal.dataContainer
				.width(modal.holder.outerWidth())
				.center(modal.background, true);

		});

	});

/* Add handler to black area click */
$(document)
	.on("click", function(event) {

		/* Get element */
		var element = $(event.target || event.srcElement);

		/* Get window */
		var modal = element.data("modalWindow")


		/* Hide window */
		if(modal)
			modal.close();

	});