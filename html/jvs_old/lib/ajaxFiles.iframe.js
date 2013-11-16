sky.AjaxFiles.IFrame = function(options) {

	/* Save options */
	this.options 	= options;
	this.files    	= options.files;
	this.input   	= options.input;
	this.url  	 	= options.url;
	this.data 	 	= options.data;
	this.callbacks 	= options.callbacks;

};

jQuery.extend(sky.AjaxFiles.IFrame.prototype, {

    getName: function(name){
    
        // get input value and remove path to normalize
        return name.replace(/.*(\/|\\)/, "");
        
    },

    cancel: function(id){
    
        this.options.onAbort(id, this.getName(this.input.value));      
        
        this.IFrame.setAttribute('src', 'javascript:false;').remove();
        
    },     
    
    /**
     * Upload file function
     */
    send: function() {    
           
        /* Variables */
		var self 	 = this;
        var input 	 = this.input;
        var fileName = this.getName(input.value);
                
        /* Create new input */
        $(input).clone().val("").insertBefore(input);
       
        /* Create elements */       
        this.IFrame = this.createIframe();
        this.form = this.createForm(this.IFrame, this.options.data).append(input);
        
        this.attachLoadEvent(this.IFrame, function() {
                                       
            var response = self.getIframeContentJSON(self.IFrame);
                                       
            if(response) self.options.onSuccess(response);
            
            // timeout added to fix busy state in FF3.6
            setTimeout(function(){ self.IFrame.remove(); }, 1);
            
        });    
        
        this.form.trigger("submit");

    }, 
    
    /**
     * Attach load event to IFrame
     */
    attachLoadEvent: function(iframe, callback){
            
        iframe.load(function(){

            if (!this.parentNode) return;

            // fixing Opera 10.53
            if (this.contentDocument &&
                this.contentDocument.body &&
                this.contentDocument.body.innerHTML == "false") return;

            callback();
            
        });
        
    },
    
    /**
     * Returns json object received by IFrame from server.
     */
    getIframeContentJSON: function(iframe){
    
        /* IFrame.contentWindow.document - for IE<7 */
        var doc = iframe.get(0).contentDocument ? iframe.get(0).contentDocument: iframe.get(0).contentWindow.document;
           
    	var response = doc.body.innerHTML
    
		/* Check for empty response */
        if(response == "") {
            if(self.callbacks) self.callbacks.onError("Данные небыли переданы"); // No data
            return false;
        }
        
        	
        /* Try to get json data */
		try {
			response = jQuery.parseJSON(response);
		} catch(e) {
            if(self.callbacks) {
            	self.callbacks.onError("Неверный формат данных"); // No data
            	console.log(response);
            }
            return false;
		}

        /* If response returned with error */
        if(response.error) {
            if(self.callbacks) self.callbacks.onError(response.text); // Execute user error handler
            return false;
        }

        return response;
    },
    
    /**
     * Creates IFrame with unique name
     */
    createIframe: function() {

        return $('<IFrame/>', { src: "javascript:false;", name: "uploadIFrame" + Math.floor(Math.random()*1000000) }).css("display", "none").appendTo('body');

    },
    
    /**
     * Creates form, that will be submitted to IFrame
     */
    createForm: function(iframe, params) {

        var queryString = this.url + "?" + jQuery.param(params);
        
        return $('<form/>', { 
	        method: "post", enctype: "multipart/form-data", action: queryString, target: iframe.attr("name") 
	    }).css("display", "none").appendTo('body');
        
    }

});