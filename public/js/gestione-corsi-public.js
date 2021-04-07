(function( $ ) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
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
        $(document).on( 'click','.em-bookings-cancel', function(e){
            $.ajax({type: 'POST',
                    url: ajaxurl, 
                    data:{
                        action:'ScriviLogCorsoPublic',
                        valori:$(this).attr('href'),
                        security:ajaxsec
                    },
                    success: function(risposta){
                    },                   
                    error: function(error) { 
                    }
            }); 
        });
        $(document).on( 'click','.attestatoCorsoxxx', function(e){
               var dati = $(this).attr('id').split(";");
              $.ajax({type: 'POST',
                    url: ajaxurl, 
                    data:{
                        action:'CreaAttestato',
                        corso:dati[0],
                        utente:dati[1],
                        security:ajaxsec
                    },
                    success: function(risposta){
                        alert(risposta);
                    },                   
                    error: function(error) { 
                    }
            }); 
        });
        $(document).on( 'click','.infoCorso', function(e){
             $( ".infoCorso" ).each(function() {
                if ($("#Info"+ $(this).attr("id")).is(":visible")) {
                    $("#Info"+ $(this).attr("id")).fadeOut('fast');;
                } else {
                    $("#Info"+ $(this).attr("id")).fadeIn('fast');;
                }
            });
        });
        $(document).on( 'click','.em-bookings-approve,.em-bookings-reject,.em-bookings-unapprove,.em-bookings-delete', function(e){
              $.ajax({type: 'POST',
                    url: ajaxurl, 
                    data:{
                        action:'ScriviLogCorso',
                        valori:$(this).attr('href'),
                        security:ajaxsec
                    },
                    success: function(risposta){
                    },                   
                    error: function(error) { 
                    }
            }); 
        });
})( jQuery );
