(function() {
    tinymce.PluginManager.add('tabs', function( editor, url ) {
        editor.addButton( 'tabs', {
            title: 'Tabs',
            image: kopa_shortcodes_globals.pluginUrl + '/js/shortcodes/icons/tabs.png',
		    onclick: function() {
                editor.insertContent('[tabs] [tab title="Tab 1"]Tab content 1[/tab] [tab title="Tab 2"]Tab content 2[/tab] [tab title="Tab 3"]Tab content 3[/tab] [/tabs]');
            }
        });
    });
})();