(function (root, factory) {
    if ( typeof define === 'function' && define.amd ) {
        define('Sequencer', factory(root));
    } else if ( typeof exports === 'object' ) {
        module.exports = factory(root);
    } else {
        root.sequencer = factory(root);
    }
})(window || this, function (root) {

    'use strict';

    // Sequencer - A fast(?) fullscreen image-sequence player.
    // (c) 2012-13
    // See README.txt or visit github (link below) for details
    // 
    // Author: 
    //      Andreas Gysin
    //      ertdfgcvb.com
    //      @andreasgysin
    //    
    // Project page:
    //      http://ertdfgcvb.com/sequencer
    //      http://github.com/ertdfgcvb/Sequencer  

    var Preloader = (function(){
        var progress;
        var queue;
        var images;
        var loaded = 0;
        var onImageLoadedCallback, onPreloadCompleteCallback; //needs a better way. Override?
        
            
        function init(config, arrayToPopulate, onImageLoaded, onPreloadComplete){

            images = arrayToPopulate; //the array that will be populated with the loaded images
            onImageLoadedCallback = onImageLoaded; //event functions… crappy way.
            onPreloadCompleteCallback = onPreloadComplete;
                
            var tot = Math.floor((config.to - config.from + 1) / config.step);
            queue = new Array(tot);
            //images = new Array(tot);
            
            buildProgress(config);  
            
            for (var i=0; i<tot; i++){
                var num = config.from + i * config.step;
                if (config.leadingZeroes > 0) num = (1e15+num+"").slice(-config.leadingZeroes);
                var src = config.folder + "/" + config.baseName + num + "." + config.ext;
                queue[i] = {src : src, id : i}; //two distinct arrays just to keep a "clean" image list instead of a custom loaderObject list, maybe this approach is overcomplicated
                images[i] = new Image(); 
            }
            
            setTimeout(function(){ //give it a bit of breath… safari needs to need that.
                var num = Math.max(1, config.simultaneousLoads);
                for (var i=0; i<num; i++){
                    loadNext();
                };
            }, 300); 
        }

        // **********************************
        //  Override init (for kerberos)
        //      - pass an array of image
        function initWithImages(config, arrayToPopulate, onImageLoaded, onPreloadComplete){

            images = arrayToPopulate; //the array that will be populated with the loaded images
            onImageLoadedCallback = onImageLoaded; //event functions… crappy way.
            onPreloadCompleteCallback = onPreloadComplete;
                
            config.from = 0;
            config.to = config.images.length - 1;

            var tot = Math.floor((config.to - config.from + 1) / config.step);
            queue = new Array(tot);
            //images = new Array(tot);
            
            buildProgress(config);  
            
            for(var i=0; i<tot; i++)
            {
                var src = config.images[i].src;
                queue[i] = {src : src, id : i}; //two distinct arrays just to keep a "clean" image list instead of a custom loaderObject list, maybe this approach is overcomplicated
                images[i] = new Image(); 
            }
            
            setTimeout(function(){ //give it a bit of breath… safari needs to need that.
                var num = Math.max(1, config.simultaneousLoads);
                for (var i=0; i<num; i++){
                    loadNext();
                };
            }, 300); 
        }
        
        function onPreloadComplete(e){
            //console.log(e.length + " images loaded.");
            if (typeof onPreloadCompleteCallback === 'function') onPreloadCompleteCallback(e); //needs absolutely a better way
        }
        
        function onImageLoaded(e){
            //console.log("loaded image [" + e.id + "]");
            if (typeof onImageLoadedCallback === 'function') onImageLoadedCallback(e); //needs absolutely a better way
        }
            
        function loadNext(){
            if (queue.length > 0){
                var o = queue.shift();
                images[o.id].src = o.src;
                //images[o.id].id = o.id; // UGLY HACK!
                images[o.id].onload = function(){
                    var id = images.indexOf(this); //not the fastest way to get an id. should be stored in a property somewhere. loaderObject?
                    onImageLoaded({img:this, id:id});
                    if (progress) progress.update(loaded);
                    loaded++;
                    if (loaded == images.length ){
                        removeProgress();
                        onPreloadComplete({images:images, length:images.length});
                    } else {
                        loadNext();
                    }               
                }
            }
        }
        
        function buildProgress(config){
            if (config.progressMode == "circle"){
                progress = document.createElement('div');
                progress.id = "progress";
                progress.style.width = config.progressDiam + "px";
                progress.style.height = config.progressDiam + "px";
                progress.style.lineHeight = config.progressDiam + "px";
                progress.style.textAlign = "center";
                progress.style.color = config.progressFgColor;
                progress.style.backgroundColor = config.progressBgColor;
                progress.style.borderRadius = config.progressDiam / 2 + "px";
                progress.style.position = "fixed";
                progress.style.left = "50%";
                progress.style.top = "50%";
                progress.style.marginTop = - config.progressDiam / 2 + "px";
                progress.style.marginLeft = - config.progressDiam / 2 + "px";
                progress.style.fontFamily = config.progressFontFamily;
                progress.style.fontSize = config.progressFontSize;
                progress.style.zIndex = 1000;
                progress.innerHTML = "loading";
                progress.update = function(num){
                    var t = Math.floor((config.to - config.from + 1) / config.step);
                    progress.innerHTML = (num + 1) + "/" + t;
                }
                document.body.appendChild(progress);
            } else if (config.progressMode == "bar") {
                progress = document.createElement('div');
                progress.id = "progress";
                progress.style.width = "0%";
                progress.style.height = config.progressHeight + "px";
                progress.style.backgroundColor = config.progressBgColor;
                progress.style.position = "fixed";
                progress.style.left = "0";
                progress.style.height = config.progressHeight;
                progress.style.bottom = "0";
                progress.style.zIndex = 1000;
                progress.update = function(num){
                    var p = Math.round(num / (config.to - config.from) * 100);
                    progress.style.width = p + "%";
                }
                document.body.appendChild(progress);
            }
        }
        
        function removeProgress(){
            if (progress) {
                document.body.removeChild(progress);
                progress = null;
            }
        }
        

        // Array fix...
        if (!Array.prototype.indexOf){
            Array.prototype.indexOf = function(elt){
                var len = this.length;
                var from = Number(arguments[1]) || 0;
                from = (from < 0) ? Math.ceil(from) : Math.floor(from);
                if (from < 0) from += len;
                for (; from < len; from++){
                    if (from in this && this[from] === elt) return from;
                }
                return -1;
            };
        }
            
        return {
            init : initWithImages, 
            images : images
        };
    })();

    // images are passed around per array indexes (id) this may change
    var Sequencer = (function () {
        var current = -1;
        var images = [];
        var playInterval;
        var playDir = 1;
        var lastLoaded = -1;
        var canvas, context;

        // configuration defaults
        var config = {
            element             : undefined,
            folder              : "",           // folder containing the image sequence
            baseName            : "",           // a basename of the files, for example "DSC00"
            from                : 1,            // first image of the sequence, will be combined with the basename
            to                  : 10,           // last image of the sequence
            leadingZeroes       : 0,
            ext                 : "jpg",        // file extention, case sensitive
            step                : 1,            // increment: to load only even images use 2, etc
            bgColor             : "#FFFFFF",    // page background color
            scaleMode           : "cover",      // as in CSS3, can be: auto, cover, contain
            direction           : "x",          // mouse direction, can be x, -x, y, -y, applies only if playMode == "mouse"
            playMode            : "mouse",      // can be: mouse, loop, pong or none (in this case a nextImage() call has to be made somewhere
            playInterval        : 200,           // interval in milliseconds beteen each frame, applies only if playMode != "mouse"  
            progressDiam        : "110",        // progress diameter
            progressFontFamily  : "Helvetica, Arial, sans-serif",
            progressFontSize    : "0.7em",
            progressBgColor     : "#000000",
            progressFgColor     : "#FFFFFF",
            progressMode        : "circle",     // can be: circle, bar, none
            progressHeight      : "5px",        // if progressMode == "bar"
            progressShowImages  : true,         // display images while loaded  
            simultaneousLoads   : 4            // how many images to load simultaneously, browser limit is 4?  
        };
            
        function init(customConfig){
            // config override
            for(var prop in customConfig)
            {
                config[prop] = customConfig[prop];
            }
            configureBody();
            Preloader.init(config, images, onImageLoaded, onPreloadComplete);  
        }
        
        function onImageLoaded(e){
            if (e.id > lastLoaded && config.progressShowImages){ // to not have a back and forward hickup… but some images will be skipped
                showImage(e.id);
                lastLoaded = e.id;
            }
        }
        
        function onPreloadComplete(e){
            setPlayMode(config.playMode);
            play();
        }
        
        function setPlayMode(mode){
            stop();
            config.playMode = mode;
        }
        
        function play(){
            stop();
            if (config.playMode === 'mouse'){
                document.addEventListener('mousemove', onMouseMove, false);
                document.ontouchmove = function(e){
                    onMouseMove(e.touches[0]);
                    return false;
                }
            } else if (config.playMode === 'loop' || config.playMode === 'pong') {
                playInterval = setInterval(nextImage, config.playInterval);
            }
        }   

        function stop(){
            document.removeEventListener('mousemove', onMouseMove);
            if (playInterval) {
                clearInterval(playInterval);
                playInterval = null;
            }
        }
                
        function nextImage(mode){
            if (!mode) mode = config.playMode;
            if(mode === 'pong') {
                current += playDir;
                if (current >= images.length-1) { //current could ev. change by other playmodes, so extra-checks are necessary
                    playDir = -1;
                    current = images.length-1;
                } else if (current <= 0){
                    playDir = 1;
                    current = 0;
                }
                showImage(current);
            } else {
                showImage(++current % images.length); //loop
            }
        }

        
        function onMouseMove(e){
            var t = images.length;
            var m, w;
            if (config.direction == "x") {
                w = window.innerWidth;
                m = e.pageX;
            } else if (config.direction == "-x") {
                w = window.innerWidth;
                m = w - e.pageX - 1;
            } else if (config.direction == "y") {
                w = window.innerHeight;
                m = e.pageY;
            } else if (config.direction == "-y") {
                w = window.innerHeight;
                m = w - e.pageY - 1;
            }

            var id = Math.min(t, Math.max(0, Math.floor(m / w * t)));
            if (id != current){
                showImage(id);
                current = id;
            }
        }


        //
        function onWindowResize(){
            canvas.height = window.innerHeight;
            canvas.width = window.innerWidth;   
            showImage(current); 
        }
        
        function configureBody(){
            canvas = config.element;
            context = canvas.getContext('2d');
        }
        
        function showImage(id){
            if (id >= 0 && id < images.length){
                var img = images[id];
                canvas.width = $("#"+canvas.id).width();
                canvas.height = $("#"+canvas.id).width()/2;
                context.drawImage(img, 0, 0, canvas.width, canvas.height);
            }
        }
        
        return {
            init : init, 
            nextImage : nextImage, 
            setPlayMode : setPlayMode, 
            play : play, 
            stop : stop
        };
    })();

    return Sequencer;
    
});