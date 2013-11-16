/* Make */
sky.services = sky.services || {};

/**
 * Folders
 * @type {{}}
 */
sky.folders = {

	ajax: {
		load: function(id) {

			/* Request */
			return sky.ajax("/ajax/folders", {id: id})
				.success(function(data) {})
				.on("error", function(error) {})
				.on("always", function() {});

		}
	}

};