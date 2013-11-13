/* Use strict mode to prevent errors */
'use strict';

angular.module("sky.directives")
	.directive("userFolder", ["skyTemplates", function(templates) {
		return {
			replace: true,
			restrict: "E",
			template: templates.getRaw("files-folder").template
		};
	}]);