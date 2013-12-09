sky.directives
	.add("user-folder", function(element, attributes) {
		sky.templates.render("user-avatar", attributes).insertBefore(element);
		element.remove();
	});