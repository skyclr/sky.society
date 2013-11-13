angular.module("skyApp")
	.controller("pageController", ["$scope", "folders", function($scope, folders) {

	}])
	.service("folders", [function() {
		return {
			load: function(id) {
				sky.ajax("/ajax/")
			}
		}
	}]);