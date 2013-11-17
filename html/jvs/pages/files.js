$(document)
	.ready(function() {

		/* Init gallery */
		page.gallery = {
			current: {}
		};

		/* Init */
		sky.services.folders.init();
		sky.services.files.init();

		/* Enable hash history */
		page.history = sky.History().on("change", function(difference) {
			sky.services.folders.ajax.load(difference.album);
		}).start();

	})
	.on("click", ".addFolder", function(event) {

		/* No # go */
		event.preventDefault();

		/* No disabled buttons go */
		if($(this).isDisabled())
			return;

		/* Show add window */
		sky.services.folders.windows.add();

	})
	.on("click", ".deleteFolder", function(event) {

		/* No # go */
		event.preventDefault();

		/* No disabled buttons go */
		if($(this).isDisabled())
			return;

		/* Show add window */
		sky.services.folders.ajax.remove($(this).parents(".folder").attr("folderId"), $(this));

	})
	.on("click", ".editFolder", function(event) {

		/* No # go */
		event.preventDefault();

		/* No disabled buttons go */
		if($(this).isDisabled())
			return;

		/* Show add window */
		sky.services.folders.windows.edit($(this).parents(".folder").data("folder"));

	})
	.on("click", ".addFiles", function(event) {

		/* No # go */
		event.preventDefault();

		/* No disabled buttons go */
		if($(this).isDisabled())
			return;

		/* Show add window */
		sky.services.files.windows.add();

	})
	.on("submit", "#addFilesForm", function() {

		/* No action go */
		event.preventDefault();

		/*  Create sender */
		var filesAjax = sky.AjaxFiles($("input[name=files]", this), "/ajax/files", { type: "add" });

		/*  Bind events */
		filesAjax.callbacks
			.on("success", function(data) {
				sky.services.files.render.single(data.file, true);
			})
			.on("always, begin", function(toProceed, total) {})
			.on("always", function(toProceed) {
				if(!toProceed)
					sky.services.files.windows.window.close();
			})
			.on("error", function(error) {
				console.log(error);
			});

		/*  Send */
		filesAjax.send();


	})
	.on("click", ".folder", function(event) {

		/* Get element */
		var element = $(event.target || event.srcElement);

		/* Load folder */
		if(element.hasClass("folder"))
			sky.services.folders.ajax.load($(this).attr("folderId"));

	})
	.on("submit", "#addFolderForm", function(event) {

		/* No action go */
		event.preventDefault();

		/* Ajax request */
		sky.services.folders.ajax.add($(this).readForm(), $(".button", this));

	})
	.on("submit", "#editFolderForm", function(event) {

		/* No action go */
		event.preventDefault();

		/* Ajax request */
		sky.services.folders.ajax.edit($(this).readForm(), $(".button", this));

	});