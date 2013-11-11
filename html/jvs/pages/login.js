angular.module("skyApp")
	.controller("pageController", ["$scope", function($scope) {
		$scope.a = 13;
		$scope.username = "Andrew"
		$scope.submit = function(form, $event) {

			$event.preventDefault();
			form.$setDirty();
			if(!form.$invalid)
				console.log("submit");
		}
	}]);