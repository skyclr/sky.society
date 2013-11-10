angular.module("sky.directives")
	.service("tip", [function() {

		// Tip constructor
		var tip = function(object, align) {

			// Self construct
			if(!this instanceof tip)
				return new tip();



			// Return
			return this;

		};

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
	.directive("", [function() {

	}]);