/* Use strict mode to prevent errors */
'use strict';

angular.module("sky.directives")
	.directive("smartLabel", ["skyTemplates", "$compile", function(templates, $compile) {
		return {
			replace: true,
			restrict: "E",
			scope: {
				title: "@"
			},
			template: "<input />",// templates.getRaw("forms-label").template
			link: {
				post: function(scope, element) {
					var label = $compile(templates.getRaw("forms-label").template)(scope);
						label.insertBefore(element);
						label.children(":first").after(element);
				}
			}
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