/* Use strict mode to prevent errors */
'use strict';

angular.module("sky.directives")
	.directive("smartLabel", ["skyTemplates", function(templates) {
		return {
			replace: true,
			restrict: "E",
			scope: {
				title: "@",
				name : "@",
				model: "=ngModel",
				type: "@"
			},
			template: templates.getRaw("forms-label").template
		}
	}])
	.directive("smartButton", ["skyTemplates", function(templates) {
		return {
			replace: true,
			restrict: "E",
			template: function(element, attrs) {
				if(attrs.type && attrs.type == "submit") return templates.getRaw("forms-submit").template;
				else return templates.getRaw("forms-button").template;
			},
			transclude: true,
			compile: function compile(tElement, tAttrs, transclude) {
				return {
					pre: function(scope) {
						transclude(scope, function(clone) {
							scope.html = clone[0].textContent;
						});
					}
				}
			}
		}
	}]);