/**
 * Sky is object that contains functions and objects that are content independent
 * and may use in any project
 */

/**
 * Holds all base objects and functions from framework
 * @type {object}
 */
var sky = sky || { };
var page = page || { data: {} };


/* Create base objects and function */
$.extend(sky, {

	/**
	 * Get function arguments list
	 * @param {function} func Function to get arguments list from
	 * @returns {Array|{index: number, input: string}}
	 */
	functionArguments: function(func) {

		/* Get arguments list */
		var str = func.toString(),
			names = str.slice(str.indexOf('(') + 1, str.indexOf(')')).match(/[^\s,]+/g);

		/* Return */
		return names ? names : [];

	},

	/**
	 * Returns object with fields listed in names from possible object
	 * @param {object} possible Object to filter
	 * @param {Array} names Names array
	 * @returns {{}}
	 */
	filterObject: function(possible, names) {

		/* Prepare */
		var filtered = {};

		/* If no */
		if(!names || !names.length)
			return filtered;

		/* Compile arguments */
		$.each(names, function(_, name) {
			if(name == "allPossibleArguments") filtered[name] = possible;
			else if(typeof possible[name] !== "undefined") filtered[name] = possible[name];
			else filtered[name] = undefined;
		});


		/* Return */
		return filtered;

	},

	/**
	 * Makes array from object values
	 * @param {object} obj Object to convert
	 * @returns {Array}
	 */
	objectToArray: function(obj) {

		/* Prepare */
		var result = [];

		/* Go through */
		$.each(obj, function(_, value) { result.push(value); });

		/* Return */
		return result;

	},

	/**
	 * Calls function with arguments specified in arguments in way they declared in function
	 * @param {function} func Function to be called
	 * @param {object} possible List of possible arguments
	 * @param {object} [context] Function context
	 */
	functionCallPossibleArguments: function(func, possible, context) {

		/* Compile arguments */
		var filtered = this.filterObject(possible, this.functionArguments(func));
		var	compiled = this.objectToArray(filtered);

		/* Call function */
		return func.apply(context || window, compiled);

	},

	/**
	 * Creates callback object that holds functions list
	 * @param {string} [flags]
	 * @returns {*}
	 * @constructor
	 */
	Callback: function(flags) {

		/* Self construct */
		if(!(this instanceof sky.Callback))
			return new sky.Callback(flags);

		/**
		 * Functions list holder
		 * @type {Array}
		 */
		this.functions = [];
		this.toRun = 0;
		this.context = this;

		/**
		 * Adds new function to stack
		 * @param {Function} f Function to add
		 * @param {Object} context Function context
		 * @param {Object} options Call options
		 */
		this.add = function(f, context, options) {
			this.functions.push({
				func: f,
				context: context || false,
				once: options && options.once
			});
			return this;
		};

		/**
		 * Removes function from list by context
		 * @param context
		 */
		this.removeByContext = function(context) {

			/* back link */
			var self = this;

			/* Find listener */
			$.each(this.functions, function(i, current) {
				if(current.context == context) {
					self.functions.splice(i, 1);
					return false;
				}
				return true;
			});

			/* Self return */
			return this;

		};

		/**
		 * Fires all functions
		 * @param {Object} context Function context
		 * @param {Array} args Arguments
		 */
		this.fire = function(context, args) {
			$.each(this.functions, function(_, func) {
				func.func.apply(func.context || context, args);
			});
		};

		/**
		 * Fires next function
		 * @param {Array|Object} args Arguments
		 * @param {Object} context Function context
		 * @param {Boolean} possible If true then function would be called view sky.functionCallPossibleArguments
		 */
		this.fireNext = function(args, context, possible) {

			/* If no more to run */
			if(this.functions.length <= this.toRun)
				return false;

			/* Set next to run */
			this.toRun++;

			/* Function to run */
			var current = this.functions[this.toRun - 1],
				result,
				func = current.func;

			/* Set context */
			context = current.context || context || window;

			/* Get function in string */
			if(typeof func == "string")
				func = context[func];

			/* If no function found */
			if(!func)
				return true;

			/* Call function */
			if(possible)
				result = sky.functionCallPossibleArguments(func, args, current.context || context) !== false;
			else
				result =  func.apply(current.context || context, args) !== false;

			/* If call once */
			if(current.once) {
				this.functions.splice(this.toRun - 2, 1);
				this.toRun--;
			}

			return result;
		};

		return this;

	}

});
