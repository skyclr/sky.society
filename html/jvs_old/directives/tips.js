/* Use strict mode to prevent errors */
'use strict';

/* Create module */
angular.module("skyApp.directives")

	/** Create tip directive */
	.directive("skyTip", ["skyTips", function(skyTips) {
		return function(_, element, attr) {
			element.on("click", function(event) {

				// No anchor go
				event.preventDefault();

				/* Get tip */
				var tip = $(this).data("tip"),
					align = $(this).hasClass("fieldHelpButton") ? "helpBubble" : false;

				// If tip already
				if(!tip)
					skyTips.show({ object: element, text: $(attr["skyTip"] + 'TipText').html(), align: align });

			});
		}
	}])

	/** Tip holder directive */
	.directive("skyTipHolder", function() {
		return function($scope, element) {

			/* Back link */
			var holder = $scope.object;

			/* Stop animation and shows */
			element.stop().show();

			/* Different actions according to tip position */
			switch($scope.align) {

				/* If show righter than input */
				case "right":
				{
					element.addClass("right").css({
						left   : holder.offset().left + holder.outerWidth(),
						top    : holder.offset().top + parseInt((holder.outerHeight() - element.outerHeight()) / 2),
						opacity: 0
					});
					element.animate({ opacity: 1, left: "+=10" }, 100);
					break;
				}
				/* If show righter than input */
				case "left":
				{
					element.addClass("left").css({
						left   : holder.offset().left - element.outerWidth(),
						top    : holder.offset().top + parseInt((holder.outerHeight() - element.outerHeight()) / 2),
						opacity: 0
					});
					element.animate({ opacity: 1, left: "-=10" }, 100);
					break;
				}
				/* If show topper */
				case "top":
				{
					element.addClass("top").css({
						left   : holder.offset().left + parseInt((holder.outerWidth() - element.outerWidth()) / 2),
						top    : holder.offset().top - element.outerHeight(),
						opacity: 0
					});
					element.css("opacity", 0).animate({ opacity: 1, top: "-=10" }, 200);
					break;
				}
				/* If we replace input with tip */
				case "instead":
				{
					element.css({
						width  : holder.outerWidth(),
						height : holder.outerHeight(),
						display: "none"
					});
					holder.fadeOut(100,function() {
						self.element.fadeIn(100);
					}).get(0).blur();
					break;
				}
				case "inside":
				{
					element.css({
						width  : holder.outerWidth(),
						height : holder.outerHeight(),
						display: "none"
					});
					element.css("opacity", 0).animate({ opacity: 1 }, 100);
					break;
				}
				case "bottom":
				{
					element.addClass("bottom").css({
						left   : holder.offset().left,
						top    : holder.offset().top + holder.outerHeight(),
						opacity: 0
					});
					element.css("opacity", 0).animate({ opacity: 1, top: "+=5" }, 200);
					break;
				}
				case "helpBubble":
				{
					element.addClass("helpBubble").css({
						left   : holder.offset().left + 5,
						top    : holder.offset().top + holder.outerHeight() - 15,
						opacity: 0
					});
					element.css("opacity", 0).animate({ opacity: 1 }, 200);
					break;
				}
				default:
					break;
			}

			$scope.$on("$destroy", function() {


				/* Remove count down if needed */
				if($scope.closeTimeout)
					clearTimeout($scope.closeTimeout);

				/* Stop animation */
				element.stop();

				/* Get know how tip was shown */
				var align = $scope.align,
					holder = $scope.object;


				/* Create end animation callback */
				var callback = function() {

					/* Remove tip */
					element.remove();

					/* Remove data */
					holder.removeData("tip");

				};

				/* If just shown */
				if(!align || !element.is(":visible"))
					callback();

				/* Right way hide */
				if(align == "right")
					element.animate({ opacity: 0, left: "+=10" }, 100, callback);

				/* Left way hide */
				if(align == "left")
					element.animate({ opacity: 0, left: "-=10" }, 100, callback);

				/* Instead way hide */
				if(align == "instead")
					element.fadeOut({ opacity: 0 }, 200, callback);

				/* Top way hide */
				if(align == "top")
					element.animate({ opacity: 0, top: "-=5" }, 200, callback);

				/* Bottom way hide */
				if(align == "bottom")
					element.animate({ opacity: 0, top: "+=5" }, 200, callback);

				/* Inside way hide */
				if(align == "inside" || align == "helpBubble")
					element.animate({ opacity: 0 }, 200, callback);

			});

		}
	})
	.run(function(skyTips) {

		$(document)

			.on("click", function(event) {

				var element = $(event.target || event.srcElement);

				var except = false;

				if(element.parents(".helpBubble").length)
					except = element.parents(".helpBubble").data("parentTip");

				if(element.is(".fieldHelpButton"))
					except = element.data("tip");

				if(element.is("[data-sky-tip]"))
					except = element.data("tip");

				skyTips.hideAll({ except: except });

			})

	});

