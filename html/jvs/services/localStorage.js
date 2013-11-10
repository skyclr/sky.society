/* Use strict mode to prevent errors */
'use strict';

/* Create module */
angular.module("skyApp")
	.factory("localStorage", function() {

		/**
		 * Functions
		 * @type {{}}
		 */
		var fn = {

			/**
			 * Loads item form database
			 * @param {*} id Unique id
			 */
			load: function(id) {

				/* Try to get item from storage */
				var item = localStorage.getItem([this.itemPrefix, id].join("-"));

				/* Return */
				return item ? $.parseJSON(item) : undefined;

			},

			/**
			 * Get all ids from database
			 * @returns {*}
			 */
			getIds: function() {

				/* If already stored */
				if(this.ids instanceof Array)
					return this.ids;

				/* Gets ids list by key */
				var itemsIds = localStorage.getItem(this.fullName),
					self = this;

				/* SAve and return */
				itemsIds = itemsIds ? itemsIds.split(", ") : [];
				this.ids = [];

				/* remove duplicates */
				$.each(itemsIds, function(_, id) {
					if(self.ids.indexOf(id) < 0)
						self.ids.push(id);
				});

				/* return */
				return this.ids;

			},

			/**
			 * Loads all element from storage
			 * @param {sky.Callbacks} [events] Events handler
			 */
			loadAll: function(events) {

				/* Item holder */
				var self = this, items = [];

				/* Go through items */
				$.each(this.getIds(), function(_, id) {

					/* Get item */
					self.load(id, events, function(item) {

						/* Add parsed */
						if(item) items.push(item);
						else delete self.ids[id];

					});

				});

				/* Return items */
				return items;

			},

			/**
			 * Save data to storage
			 * @param {string} id Object sub id
			 * @param {object} data Object to store
			 * @returns {*}
			 */
			save: function(id, data) {

				/* Save item */
				console.log(data);
				console.log([this.itemPrefix, id].join("-"), JSON.stringify(data));
				localStorage.setItem([this.itemPrefix, id].join("-"), JSON.stringify(data));

				/* Get ids */
				var ids = this.getIds();

				/* Save id */
				if(ids.indexOf(id) < 0)
					ids.push(id);

				/* Save keys */
				localStorage.setItem(this.fullName, ids.join(", "));

				/* Self return */
				return this;

			},

			/**
			 * Removes from storage
			 * @param {string} id Object sub id
			 * @returns {*}
			 */
			remove: function(id) {

				/* Init */
				var index,
					ids = this.getIds(); // Get ids

				/* Remove item */
				localStorage.removeItem([this.itemPrefix, id].join("-"));

				/* Remove from list */
				if((index = ids.indexOf(id)) > -1) {

					/* Remove id */
					ids.splice(index, 1);

					/* Save keys */
					localStorage.setItem(this.fullName, ids.join(", "));

				}

				/* Self return */
				return this;

			}

		};

		/**
		 * Init new local storage
		 * @param options
		 * @returns {*}
		 */
		var init = function(options) {

			/* Default options */
			options = options || {};

			/* Options */
			var settings  = {};

			/* Set default name */
			if(!options.name)
				options.name = "global";

			/* Set prefix */
			if(!options.prefix)
				options.prefix = options.name;

			/* Ids list */
			settings.ids = false;

			/* Set full name */
			settings.fullName = ["sky", options.name].join("-");

			/* Stored item prefix */
			settings.itemPrefix = ["sky", options.prefix].join("-");


			return jQuery.extend(true, settings, fn);

		};

		/* Supported flag */
		init.supported = !!window.localStorage;

		/* Return */
		return init;

	});