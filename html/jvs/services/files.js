/* Make */
sky.services = sky.services || {};

/**
 * files
 * @type {{}}
 */
sky.services.files = {
	init: function() {
1
		/* Get holder */
		this.render.holder = $("#files");

	},

	windows: {
		add: function() {

			/* Close previous */
			if(this.window)
				this.window.close();

			/* Create window */
			return this.window = sky.windows.Modal("files-windows-add", page.gallery.current);

		},
		file: function(file) {

			/* Close previous */
			if(this.window)
				this.window.close();

			if(file.type == "video")
				file.video = true;
			else file.image = true;

			/* Create window */
			this.window = sky.windows.Modal("files-windows-file", file);

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

			/* Return */
			return this.window;

		}
	},

	/**
	 * Rendering
	 */
	render: {

		/**
		 * Render files
		 * @param files
		 */
		files: function(files) {

			/* Remove old */
			this.holder.find(".file").remove();

			/* If no files */
			if(!files.length)
				this.holder.addClass("hidden");

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

			/* Render */
			file.render = sky.templates.render("files-thumb", file).data("file", file);

			/* Append */
			if(first)
				this.holder.children("h1").after(file.render);
			else
				this.holder.append(file.render);
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
			this.ajax = sky.ajax("/ajax/files", {id: id ? id : 0})
				.success(function(data) {

					/* Save current */
					data.current.files   = data.files;
					data.current.files = data.files;
					page.gallery.current = data.current;

					/* Render */
					sky.services.files.render.current(data.current);

				});

			/* Set default ajax callbacks */
			return this.setCallbacks(this.ajax, "load");

		},

		/**
		 * Removes file
		 * @param {int} id file id
		 * @returns {*}
		 */
		remove: function(id) {

			/* Create new request */
			page.gallery.ajax = sky.ajax("/ajax/files?type=delete", { id: id})
				.success(function() {
					$("[fileId=" + id + "]").remove();
				});

			/* Set default ajax callbacks */
			return this.setCallbacks(this.ajax, "remove");

		}
	}
};