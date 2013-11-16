angular.module("skyApp", [])
	.controller("pageController", ["$scope", function($scope) {

		console.log(321);

		$scope.submit = function() {

			console.log(123);

			// No default
			event.preventDefault();

			// Create sender
			var filesAjax = sky.AjaxFiles("input[name=file]", "");

			// Bind events
			filesAjax.callbacks
				.on("always, begin", function(toProceed) { console.log(toProceed + " file(s) left"); })
				.on("error", function(error) { console.log(error); });

			// Send
			filesAjax.send();
		}

	}]);