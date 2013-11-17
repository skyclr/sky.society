/* Make */
sky.services = sky.services || {};

/**
 * Folders
 * @type {{}}
 */
sky.services.folders = {

	folders: {

		/* Current folders list */
		current: {},
		cache: {
			folders: {},
			subFolders: {},
			subFiles: {}
		},
		holder: $("#folders"),

		/* Add folder window */
		addWindow: function() {
			this.window = sky.windows.Modal("folders-windows-add", page.gallery.current);
		},

		addToList: function(folder) {
			this.list.push(folder);
		},

		render: function() {

			/* Remove old */
			holder.find(".folder").remove();

			/* If no folders */
			if(!page.gallery.current.folders.length) {
				holder.addClass("hidden");
				return;
			} else holder.removeClass("hidden");

			/* Append thumbs */
			$.each(page.gallery.current.folders, function() {
				holder.append(sky.templates.render("folders-thumb", this));
			});

		},

		cacheFn: {
			add: function(folder, folders, files) {

				var id = folder["foledrId"],
					cache = page.gallery.folders.cache,
					self = this;

				/* Full loaded album flag */
				if(folders && files)
					folder.ready = true;

				/* Save folder */
				if(!cache.folders[id] || !cache.folders[id].ready)
					cache.folders[id] = folder;

				/* Make folders */
				if(typeof cache.subFolders[id] == "undefined")
					cache.subFolders[id] = [];

				/* Make files */
				if(typeof cache.subFiles[id] == "undefined")
					cache.subFiles[id] = [];

				/* Save sub files */
				if(files && files.length) {
					$.each(files, function() {
						if(cache.subFiles[id].indexOf(this["fileId"]) == -1)
							cache.subFiles[id].push(this["fileId"]);
					});
				}

				/* Save sub folders */
				if(folders && folders.length) {
					$.each(folders, function() {
						self.add(folder);
						if(cache.subFolders[id].indexOf(this["fileId"]) == -1)
							cache.subFolders[id].push(this["fileId"]);
					});
				}

			},
			remove: {

			},
		},

		ajax: {

			load: function(id) {

				/* Stop previous request */
				if(page.gallery.ajax)
					page.gallery.ajax.stop();

				/* If already stored in cache */
				if(page.gallery.folders.cache[id ? id : 0]) {
					page.gallery.current = page.gallery.folders.cache[id ? id : 0];
					page.gallery.render();
					return;
				}

				/* Create new request */
				page.gallery.ajax = sky.ajax("/ajax/folders", {id: id ? id : 0})
					.success(function(data) {

						/* Save current */
						page.gallery.current 		 = data.current;
						page.gallery.current.files   = data.files;
						page.gallery.current.folders = data.folders;

						/* Add to cache */
						page.gallery.folders.cache[data.current.folderId] = page.gallery.current;

						/* Render */
						page.gallery.render();

					})
					.on("error", function(error) { console.log(error) })
					.on("always", function() { page.gallery.ajax = false; });
			},

			remove: function(id) {

				/* Create new request */
				page.gallery.ajax = sky.ajax("/ajax/folders?type=delete", { id: id})
					.success(function() {
						$("[folderId=" + id + "]").remove();
					})
					.on("error", function(error) { console.log(error) })
					.on("always", function() { page.gallery.ajax = false; });

			}
		}
	},

};