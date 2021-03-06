/* Use strict mode to prevent errors */
'use strict';

angular.module("skyApp", ["ngCookies", "sky.directives"]);
angular.module("sky.directives", [])
	.run(function() {

		$(document)
			.on("mousedown", ".button, .squareButton", function() {
				if(!$(this).hasClass("disabled")) $(this).addClass("hover");
			})
			.on("mouseout mouseup", ".button, .squareButton", function() {
				$(this).removeClass("hover");
			});

	});
