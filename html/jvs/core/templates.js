sky.templates = {

	/**
	 * Stored templates strings
	 */
	templates: {},

	/**
	 * Stores compiled templates
	 */
	compiledTemplates: {},

	add: function(options) {
		console.log(options);
		this.storage.save(options.name, { template: options.template });
	},

	/**
	 * Renders specified template
	 * @param {String} name Template name
	 * @param {Object} data Inner data
	 * @param {Array} [dependencies] Template dependence from other templates
	 * @returns {*}
	 */
	render: function(name, data, dependencies) {

		/* Compile template */
		this.compile(name);

		/* Compile dependencies */
		if(dependencies) $.each(dependencies, function(_, dependency) {
			sky.templates.compile(dependency);
		});

		/* Render */
		var temp = $('<div/>').append(this.compiledTemplates[name](data));

		/* Parse directives */
		sky.directives.parse(temp);

		/* Return */
		return temp.children();

	},

	/**
	 * Compiles specified template
	 * @param {string} name Template name
	 */
	compile: function(name) {

		/* If already compiled */
		if(this.compiledTemplates[name])
			return;

		/* Load */
		this.load(name);

		/* Compile */
		this.compiledTemplates[name] = Handlebars.compile(this.templates[name]);
		Handlebars.registerPartial(name, this.templates[name]);

	},

	/**
	 * Loads specified template
	 * @param {string} name Template name
	 */
	load: function(name) {

		/* Loaded */
		var fromLS;

		/* Try to load from storage */
		if(!this.templates[name] && this.storage && (fromLS = this.storage.load(name)))
			this.templates[name] = fromLS.template;

		/* If already compiled */
		if(this.templates[name])
			return;

		/* Save template */
		this.templates[name] = $('script[type="text/template"][id='+ name +']').html();

		/* Save to LS */
		if(this.storage)
			this.storage.save(name, { template: this.templates[name] });

	}
};

$(document).ready(function() {

	/* Fetch saved templates collection */
	sky.templates.storage = sky.LocalStorage({ name: "jsTemplates" });

	/* Add to collection new templates */
	if(page.data.templates && sky.templates.storage.supported) {
		$.each(page.data.templates, function(_, template) {
			//noinspection JSUnresolvedVariable
			$.cookie("storedTemplates-" + template.path, template.date);
		});
	}

	/* Find templates and save them */
	$('script[type="text/template"]').each(function() {
		var self = $(this);
		sky.templates.add({
			name: self.attr('id'),
			template: self.html(),
			dependencies: self.attr('dependencies') ?  self.attr('dependencies').replace(" ", "").split(",") : false
		});
	});


});