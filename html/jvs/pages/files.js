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
	.on("click", ".deleteFile", function(event) {

		/* No # go */
		event.preventDefault();

		/* No disabled buttons go */
		if($(this).isDisabled())
			return;

		/* Show add window */
		sky.services.files.ajax.remove($(this).parents(".file").attr("fileId"), $(this));

	})
	.on("submit", "#addFilesForm", function() {

		/* No action go */
		event.preventDefault();

		/*  Create sender */
		var form = $(this),
			button = form.find(".button"),
			filesAjax = sky.AjaxFiles($("input[name=files]", this), "/ajax/files", { type: "add", folderId: form.find("[name=folderId]").val()  });

		/* Already in progress */
		if(!button.disable(true))
			return;

		/*  Bind events */
		filesAjax.callbacks
			.on("success", function(data) {
				sky.services.files.render.single(data.file, true);
			})
			.on("begin", function() {
				form.find(".progress").show();
			})
			.on("always, begin", function(toProceed, total) {
				form.find(".toProceed").html(toProceed);
				form.find(".total").html(total);
			})
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
		if(!element.is("a"))
			sky.services.folders.ajax.load($(this).attr("folderId"));

	})
	.on("click", ".file", function(event) {

		/* Get element */
		var element = $(event.target || event.srcElement);

		/* Load folder */
		if(!element.is("a"))
			sky.services.files.ajax.load($(this).data("file").id)

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

	})
	.on("click", "video", function() {

		if(this.ended)
			this.play();
		else if(this.paused)
			this.play();
		else
			this.pause();
	})
	.on("keypress", "body", function(event) {


		var video = $("video").get(0);
		if((event.keyCode && event.keyCode != 32) || !video)
			return;

		if(video.ended)
			video.play();
		else if(video.paused)
			video.play();
		else
			video.pause();
	});