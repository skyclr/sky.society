/* Use strict mode to prevent errors */
'use strict';

/* Create module */
angular.module("skyApp")

	/** Create service for tips viewing and binding  */
	.service("skyTips", ['skyTip', function(skyTip) {

		var tips = {};

		/* Service functions */
		return {
			show: function(options) {
				var tip = skyTip(options);
					tip.$on("$destroy", function() { delete tips[tip.$id]; console.log("remove"); });
					tips[tip.$id] = tip;
			},
			hide: function() {

			},
			hideAll: function(options) {
				$.each(tips, function(i, $scope) {

					/* Hide all except one */
					if(options.except && options.except.$id == $scope.$id)
						return;

					/* Just hide all */
					$scope.hide();

				});
			}
		};

	}])

	/** Create single tip creation service */
	.factory("skyTip",['$rootScope', "$compile", "skyTemplates", function($rootScope, $compile, skyTemplates) {

		return function(options) {

			/* Create new scope */
			var $scope = $rootScope.$new();
			$scope.text 	= options.text || "Помощь не найдена, соощите об этом в списке ошибок";
			$scope.align 	= options.align || "bottom";
			$scope.object   = $(options.object);
			$scope.hide 	= function() { this.$destroy(); };

			/* Create tip */
			$scope.tip = skyTemplates.get("tips-simple")($scope, function(element) {
				element.data("parentTip", $scope).prependTo("body");
			});

			/* Save tip */
			$scope.object.data("tip", $scope);

			/* Force to refresh template */
			$scope.$digest();

			/* Scope return */
			return $scope;

		}
	}]);