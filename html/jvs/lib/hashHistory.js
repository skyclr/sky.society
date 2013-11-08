/**
 * Object to work with hash and its changes
 * @param {function} callback Fires each time when hash changes not by this object, first time fre also
 * @param {int}		 [time]	  Interval to check hash changes
 * @constructor
 */
sky.hashHistory = function(callback, time) {

	/* Time correction */
	if(!time) time = 500;

	/* Links to function */
	this.reload 	= function(object)			{ sky.hashHistory.reload(object); };
	this.set 		= function(object, force)	{ sky.hashHistory.set(object, force); };
	this.stop 		= function()				{ sky.hashHistory.stop(); };
	this.hashCheck 	= function() {

		/* Check if hash changed */
		if(sky.hashHistory.storedHash === decodeURI(window.location.hash)) return;

		/* Category anchor redirect */
		var difference = sky.hashHistory.getDifference();

		/* Rebuild hash object on new hash str */
		sky.hashHistory.rebuild();

		/* Perform callback */
		callback(difference, sky.hashHistory.hashObjects);

	};

	/* First call immediately */
	this.hashCheck();

	/* Set interval execution */
	sky.hashHistory.intervalId = setInterval(this.hashCheck, time);

};

/** Class for work with hash */
$.extend(sky.hashHistory, /** @lends sky.hashHistory */ {

	/**
	 * Holds hash
	 * @type {string|boolean}
	 */
	storedHash: false,
	hashObjects: {},
	intervalId: 0,

	/**
	 * Gets hash and calls callback with it without init
	 * @param {function} callback Callback function
	 */
	getStartHash: function(callback) {

		/* Category anchor redirect */
		var difference = sky.hashHistory.getDifference();

		/* Perform callback */
		callback(difference, difference);

	},

	/**
	 * Get objects according to hash string
	 * @param {string} hashString String which contains key=value pairs, would be parsed to object
	 */
	getObjects: function(hashString) {

		var objects = {};

		/* Remove sharp */
		if(hashString.substr(0,1) == '#')
			hashString = hashString.slice(1, hashString.length);

		/* Split parameters */
		var subStrings = hashString.split("&");

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
	 * Remake with object
	 * @param {object} objects Object which would be used for new hash
	 */
	reload: function(objects) {

		sky.hashHistory.hashObjects = {};
		sky.hashHistory.set(objects, true);

	},

	/**
	 * Generates hash string according to stored parameters
	 */
	make: function() {
		return "#" + this.makeFromObject(this.hashObjects);
	},

	/**
	 * Generates hash string according to parameter object
	 * @param {object} object	Object which fields will be used
	 * @param {string} [name]	If set, keys would be ignored, and name=value set
	 * @return {string} Hash according to object
	 */
	makeFromObject: function(object, name) {

		var self = this, hashStr = "";

		/* Compile string */
		$.each(object, function(key, value) {
			if(hashStr != "") hashStr += "&";
			if(value instanceof Array) hashStr += self.makeFromObject(value, key);
			else hashStr += (name ? name : key) + "=" + value;
		});

		return hashStr;

	},

	/**
	 * Sets hash variable without hashChanged()
	 * @param {object}	elements Fields to be set
	 * @param {boolean}	[force]	 Replace all stored fields with elements object
	 */
	set: function(elements, force) {

		var changed = false;

		/* Force rewrite */
		if(force)
			this.hashObjects = elements;

		/* Go through elements and add or change them */
		$.each(elements, $.proxy(function(key, value) {

			/* If we need delete */
			if(value === null)
				delete this.hashObjects[key];
			else
				this.hashObjects[key] = value;

			/* Set as changed */
			changed = true;

		}, this));


		/* If any changes we rebuild hash */
		if(changed || force) {

			this.storedHash = this.make();
			if(this.storedHash == "#" && window.location.hash !== ""){
				window.location.hash = "#none";
				this.storedHash = "#none";
			}
			else if(this.storedHash != "#") window.location.hash = encodeURI(this.storedHash);
			else this.storedHash = "";
		}

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
	 * Finds difference between current stored hash and window.location.hash
	 */
	getDifference: function() {

		/* Init */
		var objects = this.getObjects(decodeURI(window.location.hash));
		return this.getObjectsDifference(this.hashObjects, objects);

	},

	/**
	 * Rebuilds stored hash parameters according to current one
	 */
	rebuild: function() {

		this.storedHash = decodeURI(window.location.hash);
		this.hashObjects = this.getObjects(this.storedHash);
		return this;

	},

	/**
	 * Stops to watch for hash changes
	 */
	stop: function() {
		clearInterval(this.intervalId);
	}

});