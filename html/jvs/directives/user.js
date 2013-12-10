sky.directives
	.add("user-avatar", function(element, attributes) {

		if(!attributes["size"]) {
			attributes["size"] = "small";
			attributes.small = true;
		}

		sky.templates.render("user-avatar", attributes).insertBefore(element);
		element.remove();
	})
	.add("user-comment", function(element, attributes) {
		attributes.text = element.html();
		sky.templates.render("user-comment", attributes).insertBefore(element);
		element.remove();
	});