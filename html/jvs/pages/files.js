angular.module("skyApp")
	.controller("pageController", ["$scope", "folders", function($scope, folders) {
		folders.load(0, $scope);
	}])
	.service("folders", [function() {
		return {
			load: function(id, $scope) {
				sky.ajax("/ajax/folders", {id: id})
					.success(function(data) { $scope.folders = data.folders })
					.always(function() { $scope.$digest(); })
			}
		}
	}]);