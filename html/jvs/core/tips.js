/** Class to work with tips */
sky.tips = {

	/**
	 * Number of last created tip
	 * @var int
	 */
	lastTipNumber: 0,

	/**
	 * Holds all <b>visible</b> tips
	 * @var object
	 */
	tips: {},

	/**
	 * Hides all visible tips
	 */
	hideAll: function() {

		/* Hide all tips */
		$.each(this.tips, function() {
			//noinspection JSPotentiallyInvalidUsageOfThis
			this.hide(); });

	},

	/**
	 * Add show/hide functions
	 * @param {string} 			selector			Object to bind tips for
	 * @param {string} 			align				Align of tip
	 * @param {object|function}	callbacks			Object with callback functions, or create function
	 * @param {function} 		[callbacks.show]	Function that fires when tip is start to show
	 * @param {function|string} [callbacks.create]	Function that returns jQuery tip object, or string with tip text
	 * @param {function} 		[callbacks.hide] 	Function callback that fires on hide
	 * @param {function|int} 	[callbacks.close] 	If exists and function then close button would be, if int that timeout close would be used
	 * @see sky.tips.tip
	 */
	bind: function(selector, align, callbacks) {

		/* Conversion */
		if(typeof callbacks !== "object")
			callbacks = { create: callbacks };

		/* Create binds */
		$(document).on({
			"mouseenter": function() {

				/* Create object */
				var self = $(this);

				/* Get tip */
				var tip = self.data("tip");

				/* Create tip data */
				if(!tip)
					tip = sky.tips.Tip(align, self, callbacks);

				/* No tip created */
				if(!tip || !tip.tip)
					return;

				/* Tip show */
				tip.show();

				/* Callbacks */
				if(callbacks && typeof callbacks.show == "function")
					callbacks.show(tip, self);

			},
			"mouseleave": function() {

				/* Ge tip */
				var tip = $(this).data("tip");

				/* No tip defined */
				if(!tip || !tip.tip) return;

				$(this).data("tip").hide(align);

			}
		}, selector);

	},

	/**
	 * Loading, !need review
	 * @param {object} object Object to show loading for
	 * @param {string} type   Type of loading to show
	 */
	loading: function(object, type) {

		/* Default type */
		if(!type) type = "medium";

		/* jQuery convert */
		object = $(object);

		/* Create loading hint */
		var loading = $('<div class="loading ' + type + '">Подождите…</div>').appendTo("body");
		loading.css({
			left: object.offset().left + parseInt((object.outerWidth()  - loading.outerWidth()) /2),
			top : object.offset().top  + parseInt((object.outerHeight() - loading.outerHeight())/2)
		});

		/* Hide object */
		object.css({ visibility: "hidden" });

		/* Extends */
		this.hide = function() {

			loading.fadeOut("fast", function() {
				loading.remove();
			});

			object.css({ visibility: "visible" });
		};

		/* Return */
		return this;

	},

	/**
	 * Creates new tip object
	 * @param {string} 	 		align	 			Way to show tip
	 * @param {object} 	 		object	 			Object to show tip for
	 * @param {object}	 		callbacks			Callback functions
	 * @param {function|string} [callbacks.create]	Function that returns jQuery tip object, or string with tip text
	 * @param {function} 		[callbacks.hide] 	Function callback that fires on hide
	 * @param {function|int} 	[callbacks.close] 	If exists and function then close button would be, if int that timeout close would be used
	 * @constructor
	 */
	Tip: function(align, object, callbacks) {

		/* Auto construct */
		if(!(this instanceof sky.tips.Tip))
			return new (sky.tips.Tip)(align, object, callbacks);

		/* Manual create tip by create function */
		if(callbacks && typeof callbacks.create == "function") {

			/* Create tip */
			this.tip = callbacks.create(object);

			/* If no tip */
			if(!this.tip) return this;

		}

		/* Back link */
		var self = this;

		/* Object set */
		this.object = $(object).data("tip", this);

		/* Align */
		this.align = align;

		/* Save callbacks */
		this.callbacks = callbacks || {};

		/* Manual create tip by create function */
		if(!callbacks || typeof callbacks.create !== "function") {

			/* Create tip callbacks, if create is text */
			var text = "Пожалуйста подождите";

			/* If text in callbacks */
			if(callbacks && typeof callbacks.create == "string")
				text = callbacks.create;

			/* Title using */
			else if(object.attr('title'))
				text = object.attr('title');

			/* Create basic tip based on title */
			this.tip = $("<div/>").addClass("tip").append('<div class="tipContent">' + text + '</div>').appendTo('body');

		}


		/* Set unique tip id */
		++sky.tips.lastTipNumber;
		this.id	= sky.tips.lastTipNumber;

		/* Save tip */
		sky.tips.tips[this.id] = this;

		/* If need close button */
		if(callbacks && callbacks.close) {

			/* If count down */
			if(typeof callbacks.close == "number")
				this.closeTimeout = setTimeout(function() { self.hide(); }, close);

			/* If close button */
			else
				$('<div/>').addClass('close').appendTo(this.tip).click(function() { self.hide(); });

		}

		return this;

	}

};

/**
 * Extends tip
 */
$.extend(sky.tips.Tip.prototype, /** @lends sky.tips.tip */ {

	/**
	 * Shows tip according to type
	 * @param {string} [align] Way it would be shown
	 */
	show: function(align) {

		/* Back link */
		var self = this;

		/* Stop animation and shows */
		this.tip.stop().show();

		/* Save way to show */
		if(align)
			this.align = align;

		/* Different actions according to tip position */
		switch(this.align) {

			/* If show righter than input */
			case "right": {
				this.tip.addClass("right").css({
					left	: this.object.offset().left + this.object.outerWidth(),
					top		: this.object.offset().top + parseInt((this.object.outerHeight() - this.tip.outerHeight())/2),
					opacity : 0
				});
				this.tip.animate({ opacity: 1, left: "+=10" }, 100);
				break;
			}
			/* If show righter than input */
			case "left": {
				this.tip.addClass("left").css({
					left	: this.object.offset().left - this.tip.outerWidth(),
					top		: this.object.offset().top + parseInt((this.object.outerHeight() - this.tip.outerHeight())/2),
					opacity : 0
				});
				this.tip.animate({ opacity: 1, left: "-=10" }, 100);
				break;
			}
			/* If show topper */
			case "top": {
				this.tip.addClass("top").css({
					left	: this.object.offset().left + parseInt((this.object.outerWidth() - this.tip.outerWidth())/2),
					top		: this.object.offset().top - this.tip.outerHeight(),
					opacity	: 0
				});
				this.tip.css("opacity", 0).animate({ opacity: 1, top: "-=3" }, 300);
				break;
			}
			/* If we replace input with tip */
			case "instead": {
				this.tip.css({
					width	: this.object.outerWidth(),
					height	: this.object.outerHeight(),
					display	: "none"
				});
				this.object.fadeOut(100, function() { self.tip.fadeIn(100); }).get(0).blur();
				break;
			}
			case "inside": {
				this.tip.css({
					width	: this.object.outerWidth(),
					height	: this.object.outerHeight(),
					display	: "none"
				});
				this.tip.css("opacity", 0).animate({ opacity: 1 }, 100);
				break;
			}
			default: break;
		}

	},

	/**
	 * Hides current tip
	 */
	hide: function() {

		/* Remove count down if needed */
		if(this.closeTimeout)
			clearTimeout(this.closeTimeout);

		/* Stop animation */
		this.tip.stop();

		/* Get know how tip was shown */
		var align = this.align;

		/* Create end animation callback */
		var callback = $.proxy(function() {

			/* Delete record from global list */
			delete sky.tips.tips[this.id];

			/* Remove data */
			this.object.removeData("tip");

			/* Remove tip */
			this.tip.remove();

			/* Callback */
			if(this.callbacks && this.callbacks.hide)
				this.callbacks.hide(this.object, this.tip);

		}, this);

		/* If just shown */
		if(!align)
			callback();

		/* Right way hide */
		if(align == "right")
			this.tip.animate({ opacity: 0, left: "+=10" }, 100, callback);

		/* Left way hide */
		if(align == "left")
			this.tip.animate({ opacity: 0, left: "-=10" }, 100, callback);

		/* Instead way hide */
		if(align == "instead")
			this.tip.fadeOut({ opacity: 0 }, 200, callback);

		/* Top way hide */
		if(align == "top")
			this.tip.animate({ opacity: 0, top: "-=5" }, 200, callback);

		/* Inside way hide */
		if(align == "inside")
			this.tip.animate({ opacity: 0 }, 200, callback);

	},

	/**
	 * Gets tips dom
	 * @returns {*}
	 */
	get: function() {
		return this.tip;
	},

	/**
	 * Sets tip text
	 * @param {string|jQuery} text What to insert to tip body
	 */
	set: function(text) {
		this.tip.children(".tipContent").html(text);
		return this;
	},

	/**
	 * Adds something to tip
	 * @param {string|jQuery} what What to append to tip body
	 */
	add: function(what) {
		this.tip.children(".tipContent").append(what);
		return this;
	}

});