(function () {
    tinymce.create("tinymce.plugins.contact_form", {
        init: function (d, e) {
        },
        createControl: function (d, e) {
            if (d == "contact_form") {
                d = e.createMenuButton("contact_form", {
                    title: "Contact",
                    image: kopa_shortcodes_globals.pluginUrl + '/js/shortcodes/icons/contact_form.png',
                    icons: false
                });
                var a = this;
                d.onRenderMenu.add(function (c, b) {
                    
                    a.addImmediate(b, "Contact Form", '[contact_form caption="Contact Us" description="Your email address will not be published. Required fields are marked *"][/contact_form]');
                    a.addImmediate(b, "Contact Info", '[contact_info title="Title" address="Address" phone="Phone number" email="Email"]');
                    
                });
                return d;
            }
            return null
        },
        addImmediate: function (d, e, a) {
            d.add({
                title: e,
                onclick: function () {
                    tinyMCE.activeEditor.execCommand("mceInsertContent", false, a)
                }
            })
        }
    });
    tinymce.PluginManager.add("contact_form", tinymce.plugins.contact_form)
})();