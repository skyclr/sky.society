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

	},

	/**
	 *
	 * Callbacks prepared object
	 * @param {Array} callbacks Callbacks list
	 * @param {Object} [flags] Flags list for jQuery.Callbacks
	 * @constructor
	 */
	Callbacks: function(callbacks, flags) {

		/* Self construction */
		if(!(this instanceof sky.Callbacks))
			return new sky.Callbacks(callbacks, flags);

		/* Add properties */
		$.extend(true, this, sky.Callbacks.extend);

		/* Create callbacks */
		$.each(callbacks || [], $.proxy(function(_, name) {
			sky.Callbacks.add.call(this, name);
		}, this));

		/* Set default flags and self context */
		return this.flags(flags).setContext();

	}

});

$.extend(sky.Callbacks, {

	extend: {

		/**
		 * Callbacks list
		 */
		advancedCallbacks: {},

		/**
		 * Sets event on callback
		 * @param {string} name Callback(s) name
		 * @param {function} func Function to be called
		 * @param {object} [context] Execution context
		 * @param {object} [options] Execution options
		 * @returns {*}
		 */
		on: function(name, func, context, options) {
			sky.Callbacks.setter.call(this, name, func, context, options);
			return this;
		},

		/**
		 * Triggers event(s)
		 * @param {string} name Event(s) name
		 * @param {object|Array} args Arguments list
		 * @param {object} options Call parameters
		 * @returns {*}
		 */
		fire: function(name, args, options) {

			sky.Callbacks.fire.call(this, name, args, jQuery.extend({ possible: true }, options || {}));
			return this;
		},

		/**
		 * Removes specified event
		 * @param {string} name Event name
		 * @param {object} listener Context
		 * @returns {*}
		 */
		off: function(name, listener) {
			sky.Callbacks.removeListener.call(this, name, listener);
			return this;
		},
		/**
		 * Flags for sky.Callback
		 * @param {object} flags Flags list
		 * @returns {*}
		 */
		flags: function(flags) {
			this.callbacksFlags = flags;
			return this;
		},

		/**
		 * Sets context for all^ where it's not specified
		 * @param {object} [cont] Execution context
		 * @returns {*}
		 */
		setContext: function(cont) {
			if(!cont) this.context = window;	// Default context
			else      this.context = cont;		// Context set
			return    this;
		}

	},

	/**
	 * Remove by listener
	 * @param {string} name Event name
	 * @param {string} listener Listener object
	 */
	removeListener: function(name, listener) {
		if(this.advancedCallbacks[name]) {
			this.advancedCallbacks[name].removeByContext(listener);
		}
	},

	/**
	 * Adds new event handler
	 * @param {string} 	 name 			Name of event
	 * @param {function|string} func 	Function be called on event fires
	 * @param {object}   [context]		Function options
	 * @param {object}   [options]		Function options
	 */
	setter:  function(name, func, context, options) {

		/* Get events names */
		var names = name.split(", ");

		$.each(names, $.proxy(function(_, name) {

			/* Create callbacks */
			if(!this.advancedCallbacks[name])
				this.advancedCallbacks[name] = sky.Callback(this.callbacksFlags);

			/* Add function */
			this.advancedCallbacks[name].add(func, context ? context : this.context, options || {});

		}, this));
	},

	/**
	 * Fires callbacks for specified event
	 * @param {string} name Name of event
	 * @param {object} args Arguments to be passed
	 * @param {object} [options] Additional options
	 */
	fire: function(name, args, options) {

		/* Success last */
		var events = sky.Callbacks.getEventsNames(name), self = this;
		options = options || {};

		/* Remove global if need */
		if(options["noGlobal"])
			events = events.slice(1);

		/* Fire events */
		$.each(events, function(_, event) {

			/* If no callback */
			if(!self.advancedCallbacks[event])
				return;

			/* Fire next */
			var next = true;

			/* Run */
			while(next)
				next = self.advancedCallbacks[event].fireNext(jQuery.extend({ event: event }, args || []), self.context, options.possible);

			/* Reset */
			if(!options.once)
				self.advancedCallbacks[event].toRun = 0;

		});

	},

	/**
	 * Get all event names from global name
	 * @param {String} name Global event name
	 * @returns {Array}
	 */
	getEventsNames: function(name) {

		/* Get events names */
		var names = name.split(", "),
			events = [];

		/* Go through */
		$.each(names, function(i, name) {

			/* Remove spaces */
			name = name.replace(" ", "");

			/* Get elements */
			var elements = name.split(".");
				events.push(elements[0]);

			/* Go through */
			for(var j = 1; j < elements.length; j++) {
				events.push(elements[0] + "." + elements[j]);
			}

			/* Global event */
			if(elements.length > 2) {
				events.push(elements.join("."));
			}

		});

		/* Return */
		return events;

	},

	/**
	 * Removes event handlers and functions
	 * @param {string} name Event name
	 */
	remove: function(name) {

		/* Delete */
		delete this.advancedCallbacks[name];

		/* Only global */
		name = sky.Callbacks.getEventsNames(name)[0];

		/* Delete functions */
		delete this[name];
		delete this["on" + name.charAt(0).toUpperCase() + name.slice(1)];
		delete this["aOn" + name.charAt(0).toUpperCase() + name.slice(1)];

	},

	/**
	 * Adds setters and firers for event
	 * @param {string} name Event name
	 */
	add: function(name) {

		/* Back link */
		var self = this;

		/* Only global */
		name = sky.Callbacks.getEventsNames(name)[0];

		/* Return */
		if(this[name]) return;

		/* Set method */
		this[name] = function(func) {
			sky.Callbacks.setter.call(self, name, func);
			return self;
		};

		/* Fire method */
		this["on" + name.charAt(0).toUpperCase() + name.slice(1)] = function() {
			sky.Callbacks.fire.call(self, name, arguments);
			return self;
		};

		/* Fire advanced method */
		this["aOn" + name.charAt(0).toUpperCase() + name.slice(1)] = function(possible) {
			sky.Callbacks.fire.call(self, name, possible, true);
			return self;
		};

	}

});