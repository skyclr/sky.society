angular.module("skyApp")
	.service("folders", ["ajax", function(ajax) {

		/**
		 * Service init
		 */
		return {

			/**
			 * Loads single folder data with sub-folders and files
			 * @param id
			 * @param $scope
			 * @returns {*}
			 */
			load: function(id, $scope) {

				/* Stop previous */
				if($scope.ajax)
					$scope.ajax.stop();

				/* Request */
				return $scope.ajax = ajax("/ajax/folders", {id: id})
					.success(function(data) {
						$scope.current 		= data.current;
						$scope.folders.list = data.folders;
						$scope.files.list   = data.files;
					})
					.on("error", function(error) { $scope.$eval("error = " + error); })
					.on("always", function() { $scope.$apply("ajax = false");  })
			},
			add: function(data, $scope) {

				/* Request */
				return sky.ajax("/ajax/folders?type=add", data)
					.success(function(data) { })
					.error(function(error) { $scope.error = error })
					.always(function() { $scope.ajax = false; $scope.$digest();  });
			}

		};


	}]);