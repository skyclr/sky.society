/**
 * Local storage class
 * @param {Object} options Creation options
 * @param {sky.Callbacks} [events] Events handler
 * @returns {*}
 * @constructor
 */
sky.LocalStorage = function(options, events) {

	/* If already */
	if(options instanceof sky.LocalStorage)
		return options;

	/* Self creating if not in constructor */
	if(!(this instanceof sky.LocalStorage))
		return new sky.LocalStorage(options, events);

	/* Options */
	options = options || {};

	/* Set default name */
	if(!options.name)
		options.name = "global";

	/* Set prefix */
	if(!options.prefix)
		options.prefix = options.name;

	/* Set full name */
	this.fullName = ["sky", options.name].join("-");

	/* Stored item prefix */
	this.itemPrefix = ["sky", options.prefix].join("-");

	/* Get events */
	this.events = events || new sky.Callbacks();

	/* Support id */
	this.supported = sky.LocalStorage.supported;

	/* Ids list */
	this.ids = false;

	/* Return */
	return this;

};

/* Supported flag */
sky.LocalStorage.supported = !!window.localStorage;

/**
 * Extending
 */
$.extend(sky.LocalStorage.prototype, {

	/**
	 * Loads item form database
	 * @param {*} id Unique id
	 * @param {sky.Callbacks} [events] Events handler
	 * @param {function} [onLoad] Calls when load complete
	 */
	load: function(id, events, onLoad) {

		/* Try to get item from storage */
		var item = localStorage.getItem([this.itemPrefix, id].join("-"));

		/* Trigger error */
		if(item === null)
			events.fire("storage.load.error", { id: id, storage: this });

		/* Call function */
		if(onLoad)
			onLoad(item ? $.parseJSON(item) : undefined);

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
		var itemsIds = localStorage.getItem(this.fullName), self = this;

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
	 * @param onLoad
	 */
	loadAll: function(events, onLoad) {

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

		/* Trigger error */
		if(!items.length)
			events.fire("storage.empty", { storage: this });

		/* Return */
		onLoad.call(this, items);

	},

	/**
	 * Save data to storage
	 * @param id
	 * @param data
	 * @param {sky.Callbacks} [events] Events handler
	 * @returns {*}
	 */
	save: function(id, data, events) {

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

		/* Trigger event */
		events.fire("storage.save.success", { storage: this, data: data, id: id });

		/* Self return */
		return this;

	},

	/**
	 * Saves all
	 * @param index
	 * @param models
	 * @param {sky.Callbacks} [events] Events handler
	 * @returns {*}
	 */
	saveAll: function(index, models, events) {
		var self = this;

		/* Go through */
		$.each(models, function() {
			self.save(this.attr(index), this.attributes, events);
		});

		return this;
	},

	/**
	 * Removes from storage
	 * @param id
	 * @param {sky.Callbacks} [events] Events handler
	 * @returns {*}
	 */
	remove: function(id, events) {

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

			/* Trigger event */
			events.fire("storage.success.remove", { storage: this, id: id });

		}
		/* If no id */
		else
			events.fire("storage.remove.error", { storage: this, id: id });


		/* Self return */
		return this;

	}
});