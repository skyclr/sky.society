/* Use strict mode to prevent errors */
'use strict';

/* Create module */
angular.module("skyApp")
	.service("skyTemplates", ["localStorage", "$compile", function(localStorage, $compile) {

		/* Create storage */
		var storage = localStorage({ name: "jsTemplates" });

		/* List of compiled templates */
		var templates = {},
			compiledTemplates = {};


		/* Methods */
		return {
			supported: localStorage.supported,
			add: function(options) {
				storage.save(options.name, { template: options.template });
			},
			get: function(name) {

				/* Return template */
				return $compile(this.getRaw(name).template);

			},
			getRaw: function(name) {

				/* Try to load */
				if(!templates[name])
					templates[name] = storage.load(name) || { template: "<p>Нет шаблона, пожалуйста, сообщете нам о этой ошибке</p>" };

				/* Return */
				return templates[name];

			}
		}

	}])
	.run(["skyTemplates", "$cookies", function(skyTemplates, $cookies) {

		/* Add to collection new templates */
		if(page.data.templates && skyTemplates.supported) {
			$.each(page.data.templates, function(_, template) {
				//noinspection JSUnresolvedVariable
				$cookies["storedTemplates-" + template.path] =  template.date;
			});
		}

		/* Find templates and save them */
		$('script[type="text/template"]').each(function() {
			var self = $(this);
			skyTemplates.add({
				name: self.attr('id'),
				template: self.html(),
				dependencies: self.attr('dependencies') ?  self.attr('dependencies').replace(" ", "").split(",") : false
			});
		});

	}]	);