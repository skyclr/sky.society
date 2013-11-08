sky.History = function(options) {

	/* Self creation */
	if(!(this instanceof sky.History))
		return new sky.History(options);

	/* Reset */
	this.options = options || {};

	/* Set events */
	this.events = this.options.events || new sky.Callbacks(["change", "set"]);
	this.options.events = this.events;

	/* Self return */
	return this;

};

/**
 * Extending
 */
$.extend(sky.History.prototype, {

	/**
	 * Stores last saved hash
	 */
	hash: "",

	/**
	 * Stores object with hash params key/value pairs
	 */
	hashObject: {},

	/**
	 * Stores events
	 */
	events: undefined,

	/**
	 * Holds hash check function interval id
	 */
	intervalId: 0,

	/**
	 * Indicates that html history api supported
	 */
	supported: false,

	/**
	 * This page base url
	 */
	base: "",

	/**
	 * Current
	 */
	current: false,

	/**
	 * Changes current path to specified
	 * @param {string} path PAth to navigate
	 */
	navigate: function(path) {
		path = path.replace("~", this.base);
		history.pushState({ oldPath: this.current, newPath: path }, path, path);
		this.current = window.location.pathname.substr(this.base.length);
		this.events.fire("navigate.path, always", { hash: this.hashObject, path: this.current });
	},

	/**
	 * Fires on path change
	 * @param {event} [event]
	 */
	change: function(event) {

		/* Hash difference holder */
		var difference = {},
		 	old = this.current,
			hashChanged = false;

		/* If api supported */
		if(this.supported) {
			this.current = window.location.pathname.substr(this.base.length);
			//this.events.fire("pathChange", $.extend({ type: "path", current: this.current }, event.state));
		}


		/* Check if hash changed */
		if(this.hash !== this.getWindowHash()) {

			/* Get difference */
			difference = this.getDifference(this.getWindowHash());

			/* Hash change flag */
			hashChanged = true;

			/* Rebuild hash object on new hash str */
			this.rebuild();

		}

		/* If nothing changed */
		if(this.current === old && !hashChanged)
			return;


		/* Fire */
		this.events.fire("change, always", { hash: this.hashObject, difference: difference, path: this.current, hashChanged: hashChanged, pathChanged: old !== this.current });

	},

	/**
	 * Navigates to specified path
	 * @param path
	 */
	setHash: function(path) {

		/* Set hash */
		window.location.hash = encodeURI(path);

	},

	/**
	 * Sets hash variable
	 * @param {object}	elements Fields to be set
	 * @param {boolean}	[force]	 Replace all stored fields with elements object
	 */
	set: function(elements, force) {

		var changed = false;

		/* Force rewrite */
		if(force)
			this.hashObject = elements;

		/* Go through elements and add or change them */
		$.each(elements, $.proxy(function(key, value) {

			/* If we need delete */
			if(value === null)
				delete this.hashObject[key];
			else
				this.hashObject[key] = value;

			/* Set as changed */
			changed = true;

		}, this));

		/* If any changes we rebuild hash */
		if(changed || force) {
			this.hash = jQuery.param(this.hashObject);
			this.setHash(jQuery.param(this.hashObject));
		}

		/* Fire */
		this.events
			.fire("set", { elements: elements })
			.fire("always", { hash: this.hashObject, path: this.current });

	},

	/**
	 * Get objects according to hash string
	 * @param {string} hash String which contains key=value pairs, would be parsed to object
	 */
	getObjects: function(hash) {

		var objects = {};

		/* Remove sharp */
		if(hash.substr(0,1) == '#')
			hash = hash.slice(1, hash.length);

		/* Split parameters */
		var subStrings = hash.split("&");

		/* Get params */
		$.each(subStrings, function(i, str) {

			var keyAndValue = str.split("=", 2);

			/* If no assign */
			if(keyAndValue.length < 2)
				return;

			/* Special hash for "=" in value  */
			keyAndValue[1] = str.substr(keyAndValue[0].length + 1);

			/* If object repeats we create array */
			if(typeof objects[keyAndValue[0]] == "undefined") objects[keyAndValue[0]] = keyAndValue[1];
			else {
				if(!(objects[keyAndValue[0]] instanceof Array)) objects[keyAndValue[0]] = [objects[keyAndValue[0]]];
				objects[keyAndValue[0]].push(keyAndValue[1]);
			}
		});

		return objects;

	},

	/**
	 * Get difference fields in objects
	 * @param {object} first  Object to compare
	 * @param {object} second Object to compare
	 */
	getObjectsDifference: function(first, second) {

		var difference = {};
		var localDiff  = false;
		var self = this;

		/* If both arrays or objects */
		if((first instanceof Array && second instanceof Array) || (first instanceof Object && second instanceof Object)) {

			/* Find what was changed or deleted in second */
			$.each(first, function(key, value) {

				/* If no such elements in second */
				if(typeof second[key] == "undefined")
					difference[key] = null; // Set to null

				/* Check if different */
				else if(localDiff = self.getObjectsDifference(value, second[key])) {
					difference[key] = localDiff;
				}

			});

			/* If was added */
			$.each(second, function(key, value) {
				if(typeof first[key] == "undefined")
					difference[key] = value;
			});

			/* Convert object to array */
			if(first instanceof Array) {
				var returnArray = [];
				$.each(difference, function(key) {
					returnArray.push(difference[key]);
				});
				difference = returnArray;
			}

		} else {
			if(first != second) return second;
			else return false;
		}

		/* No array difference */
		if(difference.length == 0) return false;
		else return difference;

	},

	/**
	 * Finds difference between current stored hash and parameter
	 * @param hash
	 * @returns {*}
	 */
	getDifference: function(hash) {

		/* Init */
		var objects = this.getObjects(decodeURI(hash));
		return this.getObjectsDifference(this.hashObject, objects);

	},

	/**
	 * Rebuilds stored hash parameters according to current one
	 */
	rebuild: function() {

		this.hash = this.getWindowHash();
		this.hashObject = this.getObjects(this.hash);
		return this;

	},

	/**
	 * Gets current window hash without "#"
	 * @returns {string}
	 */
	getWindowHash: function() {

		var hash = decodeURI(window.location.hash);

		/* Remove sharp */
		if(hash.substr(0,1) == '#')
			hash = hash.slice(1, hash.length);

		return hash;

	},

	/**
	 * Stops to watch for hash changes
	 */
	stop: function() {
		if(this.intervalId)
			clearInterval(this.intervalId);
	},

	/**
	 * Set interval execution
	 */
	start: function() {

		/* If supported */
		if(window.history) {

			/* Set base if any */
			if(this.options.base)
				this.base = this.options.base;

			/* Set handler */
			window.onpopstate = $.proxy(this.change, this);

			/* Set supported flag */
			this.supported = true;

		} else {

			/* Set flag */
			this.supported = false;

			/* Timeout */
			if(!this.intervalId)
				this.intervalId = setInterval($.proxy(this.change, this), this.options.time || 500);

		}

		/* Immediately */
		this.change();
		return this;
	},

	onOr: function(path, hash, callback, data) {
		var self = this;
		this.events.on("change", function() {
			self.findParam(path, hash, callback, data);
		});
		return this;
	},

	onOrA: function(path, hash, callback, data) {
		var self = this;
		this.events.on("always", function() {
			self.findParam(path, hash, callback, data);
		});
		return this;
	},

	on: function(hash, callback) {
		this.events.on("change", callback);
		return this;
	},

	findParam: function(path, hash, callback, data) {

		/* Reset data */
		data = data || [this.current, this.hashObject];
		var result;

		/* Fire if found */
		if(result = data[1][hash])
			callback(result);
		else if(result = data[0].match(path))
			callback(result.length ? result[1] : undefined);
		else
			callback();

		/* Self return */
		return this;

	},

	pathOrHash: function(path, hash) {
		if(this.supported)
			this.navigate(path);
		else
			this.set(hash);
	}

});