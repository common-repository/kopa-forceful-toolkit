/**
 * Kopa Shortcode
 * Author: Kopatheme
 * Licensed under GNU General Public License v3
 */

jQuery(document).ready(function() { 
	
	jQuery( '.tabs-1' ).each(function () {
        var $this = jQuery(this),
            firstTabContentID = $this.find('li a').first().attr('href');

        // add active class to first list item
        $this.children('li').first().addClass('active');

        // hide all tabs
        $this.find('li a').each(function () {
            var tabContentID = jQuery(this).attr('href');
            jQuery(tabContentID).hide();    
        });
        // show only first tab
        jQuery(firstTabContentID).show();

        $this.children('li').on('click', function(e) {
            e.preventDefault();
            var $this = jQuery(this),
                $currentClickLink = $this.children('a');

            if ( $this.hasClass('active') ) {
                return;
            } else {
                $this.addClass('active')
                    .siblings().removeClass('active');
            }

            $this.siblings('li').find('a').each(function () {
                var tabContentID = jQuery(this).attr('href');
                jQuery(tabContentID).hide();
            });

            jQuery( $currentClickLink.attr('href') ).fadeIn();

        });
    });

    jQuery( '.tabs-2' ).each(function () {
        var $this = jQuery(this),
            firstTabContentID = $this.find('li a').first().attr('href');

        // add active class to first list item
        $this.children('li').first().addClass('active');

        // hide all tabs
        $this.find('li a').each(function () {
            var tabContentID = jQuery(this).attr('href');
            jQuery(tabContentID).hide();    
        });
        // show only first tab
        jQuery(firstTabContentID).show();

        $this.children('li').on('click', function(e) {
            e.preventDefault();
            var $this = jQuery(this),
                $currentClickLink = $this.children('a');

            if ( $this.hasClass('active') ) {
                return;
            } else {
                $this.addClass('active')
                    .siblings().removeClass('active');
            }

            $this.siblings('li').find('a').each(function () {
                var tabContentID = jQuery(this).attr('href');
                jQuery(tabContentID).hide();
            });

            jQuery( $currentClickLink.attr('href') ).fadeIn();

        });
    });

    jQuery( '.tabs-3' ).each(function () {
        var $this = jQuery(this),
            firstTabContentID = $this.find('li a').first().attr('href');

        // add active class to first list item
        $this.children('li').first().addClass('active');

        // hide all tabs
        $this.find('li a').each(function () {
            var tabContentID = jQuery(this).attr('href');
            jQuery(tabContentID).hide();    
        });
        // show only first tab
        jQuery(firstTabContentID).show();

        $this.children('li').on('click', function(e) {
            e.preventDefault();
            var $this = jQuery(this),
                $currentClickLink = $this.children('a');

            if ( $this.hasClass('active') ) {
                return;
            } else {
                $this.addClass('active')
                    .siblings().removeClass('active');
            }

            $this.siblings('li').find('a').each(function () {
                var tabContentID = jQuery(this).attr('href');
                jQuery(tabContentID).hide();
            });

            jQuery( $currentClickLink.attr('href') ).fadeIn();

        });
    });
	
});