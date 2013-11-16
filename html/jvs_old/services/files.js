angular.module("skyApp")
	.service("files", [function() {
		return {
			load: function(id, $scope) {

				/* Request */
				return $scope.ajax = sky.ajax("/ajax/folders", {id: id})
					.success(function(data) {
						$scope.folders = data.folders;
						$scope.current = data.current;
					})
					.error(function(error) { $scope.error = error })
					.always(function() { $scope.ajax = false; $scope.$digest();  });
			},
			add: function(data, $scope) {

				/* Request */
				return $scope.ajax = sky.ajax("/ajax/folders?type=add", data)
					.success(function(data) { })
					.error(function(error) { $scope.error = error })
					.always(function() { $scope.ajax = false; $scope.$digest();  });
			}
		}
	}]);