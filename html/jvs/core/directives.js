sky.directives = {
	list: {},
	add: function(name, directive) {
		this.list[name] = directive;
		return this;
	},

	/**
	 * Get element attributes
	 * @param element
	 * @returns {{}}
	 */
	getAttrs: function(element) {

		// Holds attributes
		var list = {};

		// Copy them to list
		$.each(element.get(0).attributes, function(_, attr) {
			list[attr.nodeName] = attr.nodeValue;
		});

		// Return
		return list;

	},

	/**
	 * Applies directive convert to element
	 * @param element
	 * @param directive
	 */
	parseElement: function(element, directive) {
		element = $(element);
		directive(element, this.getAttrs(element));
	},

	/**
	 * Searches and replaces directives in element
	 * @param element
	 */
	parse: function(element) {
		$.each(sky.directives.list, function(tag, directive) {
			$(tag, element).each(function() {
				sky.directives.parseElement(this, directive);
			});
		});
	}

};

$(document).ready(function() {
	sky.directives.parse($("body"));
});