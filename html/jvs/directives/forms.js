angular.module("sky.directives")
	.directive("smartLabel", [function() {
		console.log(13);
		return {
			restrict: "E",
			template: '<span>Hello</span>'
		}
	}]);