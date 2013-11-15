angular.module("skyApp")
	.controller("pageController", ["$scope", "folders", "windows", function($scope, folders, windows) {

		$scope.loadFolder = function(id) { folders.load(id, $scope); };
		$scope.loadFolder(0);

		$scope.save = {
			data: {},
			submit:  function(form, $event) {

				/* No real submit */
				$event.preventDefault();

				/* Mark fields */
				form.$setDirty();

				/* Check valid */
				if(form.$invalid)
					return;

				/* Perform ajax request */
				folders.add($scope.save.data, $scope).success(function(data) {
					$scope.save.window.close();
					$scope.folders.push(data["folder"]);
				});

			}
		};

		$scope.saveFile = {
			data: {},
			submit:  function(form, $event) {

				/* No real submit */
				$event.preventDefault();

				/* Mark fields */
				form.$setDirty();

				/* Check valid */
				if(form.$invalid)
					return;

				// Create sender
				var filesAjax = sky.AjaxFiles
					($scope.saveFile.window.holder.find("input[name=files]"),
					"/ajax/files",
					jQuery.extend($scope.saveFile.data, { type: "add" }));

				// Bind events
				filesAjax.callbacks
					.on("success", function(data) { console.log(data); })
					.on("always, begin", function(toProceed) { console.log(toProceed + " file(s) left"); })
					.on("error", function(error) { console.log(error); })
					.on("progress", function(loaded) { console.log("loaded: " + loaded); });

				// Send
				filesAjax.send();

			}
		};

		/* Add folder show window func */
		$scope.addFolder = function() {
			$scope.save.data.folderId  = $scope.current.id;
			$scope.save.window = windows.Modal("folders-windows-add", $scope);
		};

		/* Add File show window func */
		$scope.addFiles = function() {
			$scope.saveFile.data.folderId  = $scope.current.id;
			$scope.saveFile.window = windows.Modal("files-windows-add", $scope);
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
					.always(function() { $scope.$digest(); $scope.ajax = false; });
			},
			add: function(data, $scope) {

				/* Request */
				return $scope.ajax = sky.ajax("/ajax/folders?type=add", data)
					.success(function(data) { })
					.always(function() { $scope.$digest(); $scope.ajax = false; });
			}
		}
	}])
	.service("files", [function() {
		return {
			load: function(id, $scope) {

				/* Request */
				return $scope.ajax = sky.ajax("/ajax/folders", {id: id})
					.success(function(data) {
						$scope.folders = data.folders;
						$scope.current = data.current;
					})
					.always(function() { $scope.$digest(); $scope.ajax = false; });
			},
			add: function(data, $scope) {

				/* Request */
				return $scope.ajax = sky.ajax("/ajax/folders?type=add", data)
					.success(function(data) { })
					.always(function() { $scope.$digest(); $scope.ajax = false; });
			}
		}
	}]);