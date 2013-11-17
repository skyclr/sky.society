/**
 *
 * Callbacks prepared object
 * @param {Array} callbacks Callbacks list
 * @param {Object} [flags] Flags list for jQuery.Callbacks
 * @constructor
 */
sky.Callbacks = function(callbacks, flags) {

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

};

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