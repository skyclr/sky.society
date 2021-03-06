$(document)
	.ready(function() {

		/* Init gallery */
		page.gallery = {
			current: {}
		};

		/* Init */
		sky.services.folders.init();
		sky.services.files.init();

		/* Load base */
		sky.services.folders.ajax.load(0);

		sky.templates.globals = {
			base: page.data.base,
			me: page.data.me
		};

		/* Enable hash history */
		page.history = sky.History().on("change", function(difference) {

			/* Load album from hash */
			if(difference.album)
				sky.services.folders.ajax.load(difference.album);

			if(difference.file)
				sky.services.files.ajax.load(difference.file);
			else if(sky.services.files.windows.window)
				sky.services.files.windows.window.close();

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
		if($(this).isDisabled() || !confirm("Вы точно хотите удалить этот альбом?"))
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
	.on("click", ".icon.info", function(event) {

		/* No # go */
		event.preventDefault();

		var bubble = $(this).parent().find(".infoBubble");

		if(bubble.is(":visible"))
			bubble.hide();
		else
			bubble.show();


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
			sky.services.files.ajax.load($(this).data("file")["fileId"]);

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
	.on("submit", ".comment.new", function(event) {

		/* No default submit */
		event.preventDefault();

		var area = $(this).find("textarea");

		/* Send request */
		sky.ajax("ajax/comments/?type=add", $(this).readForm(), $(this).find(".button"))
			.success(function(data) {
				sky.templates.render("user-comment", data.comment).prependTo(".comments .list");
				area.val("");
			})
			.error(function(error) { alert(error); });

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