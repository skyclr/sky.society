/* Use strict mode to prevent errors */
'use strict';

/* Create module */
angular.module("skyApp")
	.service("windows", ["skyTemplates", function(templates) {

		var service = {
			list: [],

			/**
			 * Creates new modal window
			 */
			Modal: function(name, data, url, lock) {

				/* Self construct */
				if(!(this instanceof service.Modal))
					return new service.Modal(name, data, url, lock);


				/* Create window */
				this.background = $(templates.getRaw("windows-modal").template);
				this.dataContainer = this.background.children();
				this.holder = this.dataContainer.children();

				/* Link */
				this.template = templates.get(name)(data).appendTo(this.holder);

				console.log(data);

				/* Callbacks */
				this.callbacks = new sky.Callbacks(["success", "error", "notSuccess", "always", "abort", "close"]);

				/* Insert */
				this.background.appendTo("body").data("modalWindow", this);
				this.background.css({ opacity: 0 }).animate({opacity: 1}, 300, function() { $(window).trigger("resize"); });

				/* Hide function */
				this.close = function() {
					this.background.remove();
				};

				/* Add to list */
				service.list.push(this);
				$(window).trigger("resize");
				this.callbacks.fire("success", this);
				return this;
			}
		};

		return service;

	}])
	.run(["windows", function(windows) {

		/* Add handler to window resize */
		$(window)
			.on("resize.modal scroll.modal", function() {

				/* If no windows */
				if(!windows["list"].length)
					return;

				/* Get sizes */
				var jWindow	  = $(window),
					jBody	  = $("body"),
					topOffset = jWindow.scrollTop() > jBody.scrollTop() ? jWindow.scrollTop() : jBody.scrollTop();

				/* Apply for windows */
				$.each(windows.list, function(_, modal) {

					/* Min values set because of this field document may be bigger */
					modal.background.css({ height: jWindow.height(), width: jWindow.width(), top: topOffset });

					/* Set dimensions and position */
					modal.dataContainer.css({ height: "auto", width: "auto" });
					modal.dataContainer
						.width(modal.holder.outerWidth())
						.center(modal.background, true);

				});

			});

		$(document)
			.on("click", function(event) {

				/* Get element */
				var element = $(event.target || event.srcElement);

				/* Get window */
				var modal = element.data("modalWindow");

				/* Hide window */
				if(modal)
					modal.close();

			})

	}]);