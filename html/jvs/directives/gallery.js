sky.directives
	.add("user-folder", function(element, attributes) {
		sky.templates.render("folders-thumb", attributes).insertBefore(element);
		element.remove();
	})
	.add("user-file", function(element, attributes) {
		sky.templates.render("file-thumb", attributes).insertBefore(element);
		element.remove();
	});