angular.module("skyApp")
	.controller("pageController", ["$scope", "folders", "windows", function($scope, folders, windows) {

		$scope.loadFolder = function(id) { folders.load(id, $scope); };
		$scope.loadFolder(0);


		$scope.save = {
			submit: function(form, $event) {

				/* No real submit */
				$event.preventDefault();

				/* Mark fields */
				form.$setDirty();

				/* Check valid */
				if(form.$invalid)
					return;

				console.log($scope);

				folders.add($scope.save).success(function() {
					$scope.save.window.close();
				});

			}
		};

		/* Add folder show window func */
		$scope.addFolder = function() {
			$scope.save.folderId  = $scope.current.id;
			$scope.save.window = windows.Modal("folders-windows-add", $scope);
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
			},
			add: function(data) {

				/* Request */
				return $scope.ajax = sky.ajax("/ajax/folders?type=add", data)
					.success(function(data) {

					})
					.always(function() { $scope.$digest(); });
			}
		}
	}]);