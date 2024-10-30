(function() {  
    tinymce.create('tinymce.plugins.posts_most_comment', {  
        init : function(ed, url) {  
            ed.addButton('posts_most_comment', {  
                title : 'Add A Block Most Comment Posts',  
                image : url+'/icons/posts_most_comment.png',  
                onclick : function() {  
                    ed.selection.setContent('[posts title="Most Comment Posts" count="8" orderby="most_comment" cats="" tags="" relation="OR" style="e.g. number, images"][/posts]');
                }  
            });  
        },  
        createControl : function(n, cm) {  
            return null;  
        }
    });  
    tinymce.PluginManager.add('posts_most_comment', tinymce.plugins.posts_most_comment);  
})();
