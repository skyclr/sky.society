/**
 * Extends base jquery functionality
 */
$.extend(jQuery.fn,
/** @lends jQuery */
{

	/** 
	 * Enables element 
	 */
    enable: function() {
        this.removeClass("disabled").removeProp("disabled").css("opacity", 1);
        return this;
    },

    /** 
     * Disables controls, if param true then return true if already disabled 
     * @param {boolean} [check] If we should check before disable
     */
    disable: function(check) {

    	/* If objetc already disabled */
		if(check && this.isDisabled()) 
			return false;

		/* Disable form elements */
        this.filter(":input").prop("disabled", "disabled");

        /* Add classes */
        this.not(":input").addClass("disabled")

        /* Add opacity */
        this.not("form").not(".button").not("label").css("opacity", 0.5);

        /* Return true if check */
        return check ? true : this;

    },

    /** 
     * Checks first of matched elements if control disabled 
     */
    isDisabled: function() {
        return this.hasClass("disabled") || this.prop("disabled") === true;
    },

    /** 
     * Formats elements on form or same
     */
    formatForm: function() {

		var maxWidth = 0;

		/* Get span with names */
       	$("*:not(.small) > span.name:not(.notFormatForm)", this).filter(":visible").each(function() {
            if($(this).width("auto").innerWidth() > maxWidth) maxWidth = $(this).innerWidth();
        }).css("width", maxWidth);
        
		/* Get inputs */
		/* removed: input.date, input.datetime, input.datehour,  */
        $("input:radio", this).filter(":visible").each(function() {
            var self = $(this);
            if(self.outerHeight() < self.parent().innerHeight()) {
                var margin = Math.floor((self.parent().innerHeight() - self.outerHeight()) /2);
                self.css("margin-top", margin);
            }
        });

		$(".d-option-checked", this).each(function() {
			var self = $(this);
			self.css("margin-left", self.parent().children(":first").outerWidth());
		});

		/* Add file */
		if(maxWidth)
			$(".d-add-file", this).css("margin-left", maxWidth + 10);

		/* Return objetcs */
        return this;

    },

	/**
	 * Centers object inside window or other one
	 * @param {object} 	obj 		Object to put this in center of or with limits { top:, left:, width:, height: }
	 * @param {boolean}	noAbsolute	Indicates that we should center inside parent
	 */
    center: function(obj, noAbsolute) {

        /* Default are zeros */
        var sizes = {
            left 	: 0,
            top 	: 0,
            width 	: 0,
            height 	: 0,
            scrollLeft: 0,
            scrollTop : 0
        };

        /* If position setted in object */
        if(typeof obj == "object" && !(obj instanceof jQuery)) $.extend(sizes, obj);
        else {

            /* Window is default object, so zeros are good */
            if(typeof obj == "undefined") obj = $(window);
            else {
                obj = $(obj);
                sizes.left = obj.offset().left;
                sizes.top  = obj.offset().top;
            }

            /* Set sizes object */
            $.extend(sizes, {
                width 		: obj.width(),
                height 		: obj.height(),
                scrollLeft 	: obj.scrollLeft(),
                scrollTop 	: obj.scrollTop()
            });

        }

        /* If we use relative center, we should center only if parent bigger than child */
        if(noAbsolute) {
			
			if(sizes.width  > this.outerWidth()  + 50) this.css("marginLeft", (sizes.width  - this.outerWidth() )  / 2 + "px");
			else this.css("marginLeft", 25);
            if(sizes.height > this.outerHeight() + 50) this.css("marginTop" , (sizes.height - this.outerHeight() ) / 2 + "px");
			else this.css("marginTop", 25);

		/* If absolute we just center */
        } else {
            this.css({
            	position: "absolute",
            	top 	: sizes.top  + ( sizes.height - this.outerHeight() ) / 2 + sizes.scrollTop  + "px",
            	left 	: sizes.left + ( sizes.width  - this.outerWidth() )  / 2 + sizes.scrollLeft + "px"
            });
        }

        /* Return  this */
        return this;

    },

	/**
	 * Convert form inputs to object
	 */
	readForm: function() {

		/* Read object */
		var read = this.serializeArray(), readed = {};

		/* Compile */
		$.each(read, function() {
			readed[this.name] = this.value;
		});

		return readed;

	}


	// Put your extensions here

});