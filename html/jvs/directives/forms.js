/* Use strict mode to prevent errors */
'use strict';

angular.module("sky.directives")
	.directive("smartLabel", ["skyTemplates", function(templates) {
		console.log(13);
		return {
			restrict: "E",
			template: templates.getRaw("forms-label")
		}
	}]);