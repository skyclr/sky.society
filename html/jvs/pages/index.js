angular.module("skyApp")
	.controller("pageController", ["$scope", function($scope) {
		$scope.submit = function(form, $event) {

			/* Mark fields */
			form.$setDirty();

			/* Check valid */
			if(form.$invalid)
				$event.preventDefault();

		}
	}]);