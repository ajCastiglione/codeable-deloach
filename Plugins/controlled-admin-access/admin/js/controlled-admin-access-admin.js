(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */
    $(document).ready(function() {

    	$('.caa_menuitems input[type="checkbox"]').change(function(e) {

			  var checked = $(this).prop("checked"),
			      container = $(this).parent(),
			      siblings = container.siblings();

			  container.find('input[type="checkbox"]').prop({
			    indeterminate: false,
			    checked: checked
			  });

			  function checkSiblings(el) {

			    var parent = el.parent().parent(),
			        all = true;

			    el.siblings().each(function() {
			      return all = ($(this).children('input[type="checkbox"]').prop("checked") === checked);
			    });

			    if (all && checked) {

			      parent.children('input[type="checkbox"]').prop({
			        indeterminate: false,
			        checked: checked
			      });

			      checkSiblings(parent);

			    } else if (all && !checked) {

			      parent.children('input[type="checkbox"]').prop("checked", checked);
			      parent.children('input[type="checkbox"]').prop("indeterminate", (parent.find('input[type="checkbox"]:checked').length > 0));
			      checkSiblings(parent);

			    } else {

			      el.parents("li").children('input[type="checkbox"]').prop({
			        indeterminate: true,
			        checked: false
			      });

			    }

			  }

			  checkSiblings(container);
		});
		$('.caa_arrow').click(function(e){
			jQuery('#subitem_menu_' + $(this).attr('data-id')).toggle();
			$(this).toggleClass('dashicons-arrow-down-alt2');
			$(this).toggleClass('dashicons-arrow-right-alt2');
		});
		$('#caa_gen_password').click(function(e){
			$('#user_password').val(caa_makePasswd());
		});
    });

})( jQuery );

function caa_makePasswd() {
  var passwd = '';
  var chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789#$@';
  for (i=1;i<15;i++) {
    var c = Math.floor(Math.random()*chars.length + 1);
    passwd += chars.charAt(c)
  }

  return passwd;

}