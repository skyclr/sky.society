$(document)
	.ready(function() {

		var history = sky.History()
			.on("change", function(difference) {

				/* On change */
				sky.services.folders.ajax.load(difference["folder"] || 0);

			}).start();

	});