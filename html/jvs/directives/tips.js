angular.module("sky.directives")
	.service("tip", [function() {

		// Tip constructor
		var tip = function(object, align) {

			// Self construct
			if(!this instanceof tip)
				return new tip(object, align);

			// Save object
			this.object = $(object).data("tip", this);

			// Return
			return this;

		};

		// Set prototype
		jQuery.extend(tip.prototype, {

		});

		// Self return
		return tip;

	}])
	.service("tips", ["tip", function(tip) {

		var tips = {

		}, lastId = 0;

		return {
			/**
			 * Hides all tips
			 */
			hideAll: function() {
				$.each(tips, function(id) { this.hide(); delete tips[id]; });
			}



		}

	}])
	.directive("tip", [function() {
		return {
			link: function (scope, element) {
				console.log(scope)
			}
		}
	}]);