/**
 * This is class to work with templates
 * @type {*|{}}
 */
sky.templates = sky.templates || {};


$.extend(sky.templates, {

	supported: !!window.localStorage,

	/**
	 * Template rendering
	 * @param {string} name Template name
	 * @param {object} options In template used object
	 */
	render: function(name, options) {

		/* Get template from collection */
		var template = this.templatesCollection.get(name);

		/* If template exists */
		if(template)
			return template.render(options);
		else
			return "";
	},

	/**
	 * Compiles and return
	 * @param {string} name
	 */
	compile: function(name) {

		/* Get template from collection */
		var template = this.templatesCollection.get(name);

		/* Compile */
		template.compile();

	},

	template: function(tempalte, options) {
		return Handlebars.compile(tempalte)(options);
	},

	/**
	 * Single template model
	 */
	model: sky.model({ name: "skyTemplatesModel" }).fields({
		template	: false,
		rendered	: false,
		dependencies: false
	}).add({

		/**
		 * Renders current template
		 * @param {object} data Template parameters
		 * @returns {*}
		 */
		render: function(data) {

			/* Compile */
			this.compile();

			/* Render template */
			return this.attr("rendered")(data);

		},

		/**
		 * compiles current template
		 * @returns {*}
		 */
		compile: function() {

			/* Make template */
			if(!this.attr("rendered")) {

				/* Render */
				var rendered = Handlebars.compile(this.attr("template"));
				Handlebars.registerPartial(this.attr("name"), rendered);

				/* Get dependencies */
				var dependencies = this.attr('dependencies');

				/* Compile dependencies */
				if(dependencies) $.each(dependencies, function(_, name) {
					sky.templates.compile(name);
				});

				/* Set rendered */
				this.attr("rendered", rendered);

			}

			/* Return */
			return rendered;

		}

	}),

	/**
	 * Templates collection with save/load
	 */
	templatesCollection: sky.collection({

		/**
		 * What models to store
		 */
		model: SG.ED("skyTemplatesModel").getValue(),

		/**
		 * Index for storing data
		 */
		index: "name"

	}).localStorage("jsTemplates").new()
});