/**
 * This class provides image area select
 */
sky.areaSelect = {

	/**
	 * Make area pick for image
	 * @param {*}    			image       Image to pick area from
	 * @param {object|number}   proportions Area proportions, must have width and height if object
	 * @param {object}          [dimensions]  Area start width and height
	 * @param {object}          [position]    Area start position
	 * @constructor
	 */
	image: function(image, proportions, dimensions, position) {

		/* Auto-construct */
		if(!this instanceof sky.areaSelect.image)
			return new (sky.areaSelect.image)(image, proportions, dimensions, position);

		/* Convert */
		if(typeof proportions == 'object')
			proportions = proportions["width"] / proportions["height"];

		/* Image get */
		image = $(image);

		/* Back link */
		var self = this;

		/* Get image position */
		var field = { width: image.innerWidth(), height: image.innerHeight() };

		/* Counts */
		var align = sky.areaSelect.fn.correct(field, dimensions, proportions, position);

		/* Make new holder */
		this.holder = $("<div/>").append(image.clone().css("display", "block")).insertBefore(image.hide());

		/* Crate shadow */
		this.shadow = $('<div/>').addClass("areaPickShadow").appendTo(this.holder).css({
			width		: field.width,
			height		: field.height,
			marginTop	: -1 * image.outerHeight(),
			clear		: "both"
		});

		/* Make picker */
		this.picker = $('<div/>').addClass('areaPickPicker').css("background", "url(" + image.attr("src") + ") no-repeat").appendTo(this.shadow).draggable({
			containment	: this.shadow,
			drag		: function(event, ui) {
				$(this).css("background-position", "-" + ui.position.left + "px -" + ui.position.top + "px");
				if(self.onChange) self.onChange(self.getCurrent(), image);
			}
		});

		/* Make resizable */
		this.picker.resizable({
			minWidth	: align.dimensions.minWidth,
			minHeight	: align.dimensions.minHeight,
			aspectRatio	: proportions ? proportions : false,
			containment	: this.shadow,
			handles		: { se: $('<div/>').addClass('scaler ui-resizable-handle ui-resizable-se').appendTo(this.picker) },
			resize      : function() {
				if(self.onChange) self.onChange(self.getCurrent(), image);
			}
		});

		/* Set positions */
		this.picker.css({
			backgroundPosition: "-" + align.position.left + "px -" + align.position.top + "px",
			left	: align.position.left,
			top		: align.position.top,
			width	: align.dimensions.width,
			height	: align.dimensions.height
		});

		/* Return current parameters */
		this.getCurrent = $.proxy(function() {
			return {
				left	: this.picker.position().left,
				top		: this.picker.position().top,
				width	: this.picker.width(),
				height	: this.picker.height()
			};
		}, this);

		/* Saves on change */
		this.change = $.proxy(function(fn) {

			/* Set */
			this.onChange = fn;

			/* Auto trigger */
			this.onChange(this.getCurrent(), image);

			/* Self link */
			return this;

		}, this);

		/* Closes current picker */
		this.close = $.proxy(function() {
			var result = this.getCurrent();
			this.holder.remove();
			image.show();
			return result;
		}, this);

		/* Return */
		return this;

	},

	/**
	 * Technical functions
	 */
	fn: {

		/**
		 * Corrects parameters
		 * @param {object} field		Width and height if object
		 * @param {object} dimensions	Width and height of selected zone
		 * @param {Number} proportions	Width/height of selected zone
		 * @param {object} position		Select stat position
		 * @returns {object} dimensions and position
		 */
		correct: function(field, dimensions, proportions, position) {

			/* Make empty object */
			if(!dimensions)
				dimensions = {};

			/* Make empty object */
			if(!position)
				position = {};

			/* Set width */
			if(!dimensions.width)
				dimensions.width = Math.floor(field.width / 2);
			else if(dimensions.width > field.width)
				dimensions.width = field.width;

			/* Set height */
			if(!dimensions.height) {
				if(!proportions)
					dimensions.height = Math.floor(field.height / 2);
				else
					dimensions.height = Math.floor(dimensions.width / proportions);
			}

			/* Correct if out of range */
			if(dimensions.height > field.height) {
				dimensions.height = field.height;
				if(proportions) dimensions.width = Math.floor(dimensions.height * proportions);
			}

			/* Set min width */
			if(!dimensions.minWidth)
				dimensions.minWidth = false;

			/* Set min height */
			if(!dimensions.minHeight)
				dimensions.minHeight = false;

			/* Set center base positions if none */
			if(!position.left)
				position.left = Math.floor((field.width - dimensions.width) / 2);

			/* Set base top position */
			if(!position.top)
				position.top = Math.floor((field.height - dimensions.height) / 2);

			/* Correct left position */
			if(position.left + dimensions.width > field.width)
				position.left = field.width - dimensions.width;

			/* Correct top position */
			if(position.top + dimensions.height > field.height)
				position.top = field.height - dimensions.height;

			/* Return */
			return {
				position	: position,
				dimensions	: dimensions
			};

		}

	}

};