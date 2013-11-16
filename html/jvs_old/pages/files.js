angular.module("skyApp")
	.controller("pageController", ["$scope", "folders", "windows", function($scope, folders, windows) {

		jQuery.extend($scope, {
			current: {},

			folders: {
				list: [],
				load: function(id) {
					folders.load(id, $scope);
				},
				save : {
					data: {},

					/**
					 * On folder add form submit
					 * @param form Current form
					 * @param $event Event
					 */
					submit:  function(form, $event) {

						/* No real submit */
						$event.preventDefault();

						/* Mark fields */
						form.$setDirty();

						/* Check valid */
						if(form.$invalid)
							return;

						/* Perform ajax request */
						folders.add($scope.folders.save.data, $scope)
							.success(function(data) {
								$scope.folders.save.window.close();
								$scope.folders.list.push(data["folder"]);
							});

					}
				},

				/**
				 * Add folder show window func
				 * @param $event Event
				 */
				add: function($event) {

					/* No real submit */
					$event.preventDefault();

					/* Set id to scope */
					this.save.data.folderId  = $scope.current.folderId;

					/* Make window */
					this.save.window = windows.Modal("folders-windows-add", $scope);

				}
			},

			files: {
				list: [],
				save: {
					data: {},
					submit:  function(form, $event) {

						/* No real submit */
						$event.preventDefault();

						/* Mark fields */
						form.$setDirty();

						/* Check valid */
						if(form.$invalid)
							return;

						/*  Create sender */
						var filesAjax = sky.AjaxFiles(
								this.window.holder.find("input[name=files]"),
								"/ajax/files",
								jQuery.extend(this.data, { type: "add" })
							);

						/*  Bind events */
						filesAjax.callbacks
							.on("success", function(data) {
								$scope.files.list.push(data.file);
							})
							.on("always, begin", function(toProceed, total) {
								$scope.files.save.toProceed = toProceed;
								$scope.files.save.total = total;
							})
							.on("always", function(toProceed) {
								$scope.$digest();
								if(!toProceed)
									$scope.files.save.window.close();
							})
							.on("error", function(error) {
								console.log(error);
							});

						/*  Send */
						filesAjax.send();

					}
				},

				/* Add File show window func */
				add: function($event) {

					/* No real submit */
					$event.preventDefault();

					this.save.data.folderId  = $scope.current.folderId;
					this.save.window = windows.Modal("files-windows-add", $scope);
				}
			}

		});

		var history = sky.History();

			history.on("change", function(difference) {
				if(difference.album)
					$scope.folders.load(difference.album);
			}).start();

		$scope.$watch("current.folderId", function(newValue) {
			newValue = newValue || 0;
			history.set({ album: newValue });
		});

		$scope.folders.load(0);

	}]);