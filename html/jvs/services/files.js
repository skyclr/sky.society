/* Make */
sky.services = sky.services || {};

/**
 * files
 * @type {{}}
 */
sky.services.files = {
	init: function() {

		/* Get holder */
		this.render.holder = $("#files");
		this.render.list =  this.render.holder.find(".list");


		/* Give me more button */
		var more = this.render.more = $("<a/>").addClass("more").html("Показать еще"),

			/* Back link */
		 	self = this,

			/* Bind */
			win = $(window).on("scroll", function() {
				if(more.is(":visible") && more.hasClass("clicked") && win.scrollTop() + win.innerHeight() > more.offset().top)
					more.trigger("click");
			});


		/* Bind */
		$(document).on("click", "a.more", function() {

			/* In progress */
			if(more.hasClass("loading"))
				return;

			/* Load more */
			sky.services.files.ajax.more(self.render.list.find(".file").length)
				.always(function() { more.removeClass("loading"); });

			/* Add class */
			more.addClass("loading").addClass("clicked");

		});

	},

	windows: {
		add: function() {

			/* Close previous */
			if(this.window)
				this.window.close();

			/* Create window */
			return this.window = sky.windows.Modal("files-windows-add", page.gallery.current);

		},
		file: function(data) {

			/* Close previous */
			if(this.window)
				this.window.close();

			if(data.file.type == "video")
				data.file.video = true;
			else data.file.image = true;

			data.file.countedHeight = 900 / parseInt(data.file.width) * parseInt(data.file.height);

			/* Create window */
			this.window = sky.windows.Modal("files-windows-file", data);

			/* Resize on image load */
			this.window.holder.find("img").on("load", function() {
				if($(this).is(":visible"))
					$(window).trigger("resize").trigger("resize");
			});

			this.window.holder.find("video").on("loadeddata", function() {
				if($(this).is(":visible"))
					$(window).trigger("resize").trigger("resize");
			});

			/* Special class for file window */
			this.window.dataContainer.addClass("fillView");
			this.window.background.addClass("fillView");

			page.history.set({ file: data.file.fileId });

			this.window.callbacks.on("close", function() {
				page.history.set({ file: null });
			});

			/* Return */
			return this.window;

		}
	},

	/**
	 * Rendering
	 */
	render: {

		append: function(files) {

			/* Append thumbs */
			$.each(files, function() { sky.services.files.render.single(this); });

			/* Remove more link */
			if(files.length < 30)
				this.more.remove();

		},

		drawMore: function() {

			/* Append */
			this.more.appendTo(this.holder).removeClass("clicked");

		},

		/**
		 * Render files
		 * @param files
		 */
		files: function(files) {

			/* Remove old */
			this.list.find(".file").remove();

			/* If no files */
			if(!files.length)
				this.holder.addClass("hidden");

			if(files.length > 29)
				this.drawMore(); /* Draws more button */
			else this.more.remove();

			/* Append thumbs */
			$.each(files, function() { sky.services.files.render.single(this); });


		},

		/**
		 * Renders single file
		 * @param {object} file file data
		 * @param [first]
		 */
		single: function(file, first) {

			/* Make visible */
			this.holder.removeClass("hidden");

			/* Set video flag */
			file.video = file.type == "video";

			/* Render */
			file.render = sky.templates.render("files-thumb", file).data("file", file);

			/* Append */
			if(first)
				this.list.prepend(file.render);
			else
				this.list.append(file.render);
		},


		/**
		 * Delete
		 * @param id
		 */
		remove: function(id) {

			/* Delete */
			this.list.find("[fileId=" + id + "]").remove();

			/* If no folders */
			if(!this.list.find(".file").length)
				this.holder.addClass("hidden");

		}

	},

	/**
	 * Requesting
	 */
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

		more: function(offset) {

			/* Stop previous request */
			if(this.ajax)
				this.ajax.stop();

			/* Create new request */
			this.ajax = sky.ajax("/ajax/files", { type: "more", id: page.gallery.current.folderId, offset: offset })
				.success(function(data) {

					/* Save offset */
					page.gallery.current.offset = offset;

					/* Render */
					sky.services.files.render.append(data["files"]);

				});

			/* Set default ajax callbacks */
			return this.setCallbacks(this.ajax, "more");

		},

		/**
		 * Loads specified file data and renders it
		 * @param {int} id file id
		 * @returns {*}
		 */
		load: function(id) {

			/* Stop previous request */
			if(this.ajax)
				this.ajax.stop();

			/* Create new request */
			this.ajax = sky.ajax("/ajax/files?type=load", {id: id ? id : 0})
				.success(function(data) {
					sky.services.files.windows.file(data);
				});

			/* Set default ajax callbacks */
			return this.setCallbacks(this.ajax, "load");

		},

		/**
		 * Removes folder
		 * @param {int} id Folder id
		 * @returns {*}
		 * @param [lock]
		 */
		remove: function(id, lock) {

			/* Create new request */
			this.ajax = sky.ajax("/ajax/files?type=delete", { id: id }, lock)
				.success(function() {
					sky.services.files.render.remove(id);
				});

			/* Set default ajax callbacks */
			return this.setCallbacks(this.ajax, "remove");

		}
	}
};