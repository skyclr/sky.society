angular.module("skyApp")
	.controller("pageController", ["$scope", "folders", "windows", function($scope, folders, windows) {

		$scope.loadFolder = function(id) { folders.load(id, $scope); };
		$scope.loadFolder(0);

		$scope.addFolder = function() {
			windows.Modal("folders-windows-add", $scope);
		};

	}])
	.service("folders", [function() {
		return {
			load: function(id, $scope) {

				/* Request */
				return $scope.ajax = sky.ajax("/ajax/folders", {id: id})
					.success(function(data) {
						$scope.folders = data.folders;
						$scope.current = data.current;
					})
					.always(function() { $scope.$digest(); });
			}
		}
	}]);