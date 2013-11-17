sky.directives
	.add("smart-label", function(element, attributes) {
		sky.templates.render("forms-label", attributes).insertBefore(element);
		element.remove();
	})
	.add("smart-button", function(element, attributes) {
		attributes.value = element.html();
		sky.templates.render(attributes.type == "submit" ? "forms-submit" : "forms-button", attributes).insertBefore(element);
		element.remove();
	});