/* Make */
sky.services = sky.services || {};

/**
 * Folders
 * @type {{}}
 */
sky.services.folders = {
	init: function() {

		/* Get holder */
		this.render.holder = $("#folders");

	},

	windows: {
		add: function() {
			return this.window = sky.windows.Modal("folders-windows-add", page.gallery.current);
		},
		edit: function(folder) {
			return this.window = sky.windows.Modal("folders-windows-edit", folder);
		}
	},

	/**
	 * Rendering
	 */
	render: {

		/**
		 * renders whole folder
		 * @param {object} current Current folder data
		 */
		current: function(current) {

			/* Render folders list */
			this.folders(current["folders"]);

			/* Render files list */
			sky.services.files.render.files(current["files"]);

			$(".toolbar .path").html(sky.templates.render("folders-path", current));

			/* Set hash */
			page.history.set({ album: page.gallery.current.folderId ? page.gallery.current.folderId : null });

		},

		/**
		 * Render folders
		 * @param folders
		 */
		folders: function(folders) {

			/* Remove old */
			this.holder.find(".folder").remove();

			/* If no folders */
			if(!folders.length)
				this.holder.addClass("hidden");

			/* Append thumbs */
			$.each(folders, function() { sky.services.folders.render.single(this); });

		},

		/**
		 * Renders single folder
		 * @param {object} folder folder data
		 * @param [first]
		 */
		single: function(folder, first) {

			/* Make visible */
			this.holder.removeClass("hidden");

			/* Render */
			folder.render = sky.templates.render("folders-thumb", folder).data("folder", folder);

			/* Append */
			if(first instanceof jQuery)
				first.after(folder.render);
			else if(first)
				this.holder.children("h1").after(folder.render);
			else
				this.holder.append(folder.render);

			/* Return rendered */
			return folder.render;
		},

		/**
		 * Delete
		 * @param id
		 */
		remove: function(id) {

			/* Delete */
			this.holder.find("[folderId=" + id + "]").remove();

			/* If no folders */
			if(!this.holder.find(".folder").length)
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

		/**
		 * Loads specified folder data and renders it
		 * @param {int} id Folder id
		 * @param [lock]
		 * @returns {*}
		 */
		load: function(id, lock) {

			lock = $(".folder, .file");

			/* Stop previous request */
			if(this.ajax)
				this.ajax.stop();

			/* Create new request */
			this.ajax = sky.ajax("/ajax/folders", {id: id ? id : 0}, lock)
				.success(function(data) {

					/* Save current */
					data.current.folders = data.folders;
					data.current.files 	 = data.files;
					data.current.path 	 = data.path;
					page.gallery.current = data.current;

					/* Render */
					sky.services.folders.render.current(data.current);

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
			this.ajax = sky.ajax("/ajax/folders?type=delete", { id: id }, lock)
				.success(function() {
					sky.services.folders.render.remove(id);
				});

			/* Set default ajax callbacks */
			return this.setCallbacks(this.ajax, "remove");

		},

		/**
		 * Performs add folder request
		 * @param {object} folder New folder data
		 * @param lock
		 * @returns {*}
		 */
		add: function(folder, lock) {

			/* Perform ajax request */
			this.ajax = sky.ajax("/ajax/folders?type=add", folder, lock)
				.success(function(data) {
					sky.services.folders.render.single(data["folder"], true);
					sky.services.folders.windows.window.close();
				});

			/* Set default ajax callbacks */
			return this.setCallbacks(this.ajax, "add");

		},
		/**
		 * Performs add folder request
		 * @param {object} folder New folder data
		 * @param lock
		 * @returns {*}
		 */
		edit: function(folder, lock) {

			var old = $("[folderId="+ folder.folderId +"]");

			/* Perform ajax request */
			this.ajax = sky.ajax("/ajax/folders?type=change", folder, lock)
				.success(function(data) {
					sky.services.folders.render.single(data["folder"], old);
					old.remove();
					sky.services.folders.windows.window.close();
				});

			/* Set default ajax callbacks */
			return this.setCallbacks(this.ajax, "add");

		}

	}
};