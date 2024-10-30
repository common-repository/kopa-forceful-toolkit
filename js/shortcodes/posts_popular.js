(function() {  
    tinymce.create('tinymce.plugins.posts_popular', {  
        init : function(ed, url) {  
            ed.addButton('posts_popular', {  
                title : 'Add A Block Popular Posts By View Count',  
                image : url+'/icons/posts_popular.png',  
                onclick : function() {  
                    ed.selection.setContent('[posts title="Popular Posts" count="8" orderby="popular" cats="" tags="" relation="OR" style="e.g. number, images"][/posts]');
                }  
            });  
        },  
        createControl : function(n, cm) {  
            return null;  
        }
    });  
    tinymce.PluginManager.add('posts_popular', tinymce.plugins.posts_popular);  
})();
