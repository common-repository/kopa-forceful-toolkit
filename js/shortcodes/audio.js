(function() {  
    tinymce.create('tinymce.plugins.soundcloud', {  
        init : function(ed, url) {  
            ed.addButton('soundcloud', {  
                title : 'Soundcloud',  
                image : url+'/icons/audio.png',  
                onclick : function() {  
                    ed.selection.setContent('[soundcloud]https://soundcloud-url[/soundcloud]');
                }  
            });  
        },  
        createControl : function(n, cm) {  
            return null;  
        }
    });  
    tinymce.PluginManager.add('soundcloud', tinymce.plugins.soundcloud);  
})();

