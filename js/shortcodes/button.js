(function() {
    tinymce.PluginManager.add('button', function( editor, url ) {
        editor.addButton( 'button', {
            title: 'Add a button',
            image: kopa_shortcodes_globals.pluginUrl + '/js/shortcodes/icons/button.png',
            onclick: function() {
                editor.insertContent('[button size="e.g. small, medium, big" link="" target="e.g. _self, _blank"]'+editor.selection.getContent()+'[/button]');
            }
        });
    });
})();