/* Make */
sky.services = sky.services || {};

/**
 * Messages
 * @type {{}}
 */
sky.services.messages = {

	ajax: {
		/**
		 * Sets default callbacks
		 * @param ajax
		 * @param type
		 * @returns {*}
		 */
		setCallbacks: function(ajax, type) {

			/* BAck link */
			var self = this;

			/* Set callbacks */
			return ajax
				.on("error", function(error) { alert(error + "(" + type + ")"); })
				.on("always", function() { self.ajax = false; });

		},

		/**
		 * Loads more messages
		 * @param offset
		 * @returns {*}
		 */
		more: function(offset) {

			/* Stop previous request */
			if(this.ajax)
				this.ajax.stop();

			/* Create new request */
			this.ajax = sky.ajax("/ajax/messages", { type: "more", offset: offset })
				.success(function(data) {

					/* Render */
					sky.services.messages.render.append(data["messages"]);

				});

			/* Set default ajax callbacks */
			return this.setCallbacks(this.ajax, "more");

		}

	}

};