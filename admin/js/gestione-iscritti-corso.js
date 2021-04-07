jQuery(document).ready(function($){
    $("#filtroscuole").change(function(e){
        var str="";
         $( "select option:selected" ).each(function() {
           str = $(this).val();
        }); 
         $.ajax({type: 'POST',
            url: ajaxurl, 
            data:{
                action:'FiltroScuola',
                idCorso:$("#Idcorso").attr('value'),
                cmscuola:str,
                security:ajaxsec
            },
            success: function(risposta){
                $("#UtentiDisp").html(risposta);
            },                   
       }); 
    });
    $("#CercaSubmit").on("click",function(e){
		$.ajax({type: 'POST',
		url: ajaxurl,
		data:{
			action:'FiltroCorsista',
			corsista:$("#search_corsista-input").val(),
			security:ajaxsec
		},
		success: function(risposta) {
			$("#UtentiDisp").html(risposta);
		},
		});
 	});
    $("#AddUserByEmail").on("click",function(e){
		$.ajax({type: 'POST',
		url: ajaxurl,
		data:{
			action:'AddUserByEmail',
			elencomail:$("#elencoMail").serialize(),
			security:ajaxsec
		},
		success: function(risposta) {
			$("#UtentiDisp").html(risposta);
		},
		});
 	});
    $("#ButtonSubmit").on("click",function(e){
       if($('#UtentiAss li').length==0){
            alert("Devi selezionare almenu un iscritto da assegnare");
            return false;        
        }       
        var UtentiArray = [];
        $('#UtentiAss li').each(function(){
            UtentiArray.push($(this).attr('id'));
        });
        var utenti=UtentiArray.join(); 
        $('#FormIscritti').append( '<input type="hidden" name="Iscritti" value="'+utenti+'"/>' );
     });
    $("#ButtonSubmitMigra").on("click",function(e){
        if($('#UtentiTra li').length==0){
            alert("Devi selezionare almenu un corsista da trasferire");
            return false;        
        }
        if($("input[name='CorsoTrasferimento']:checked").length==0){
            alert("Devi selezionare il corso prima di proseguire");
            return false; 
        }      
      var UtentiArray = [];
        $('#UtentiTra li').each(function(){
            UtentiArray.push($(this).attr('id')+";"+$(this).text());
        });
        var utenti=UtentiArray.join(); 
        $('#FormMigraIscritti').append( '<input type="hidden" name="Trasferiti" value="'+utenti+'"/>' );
     });
    $( "#tabsgestiscritti" ).tabs();    
    $("#ButtonSubmitDuplica").click(function(e){
         if($('#UtentiMig li').length==0){
            alert("Devi selezionare almenu un corsista da duplicare");
            return false;        
        }
        if($("input[name='CorsoDestinazione']:checked").length==0){
            alert("Devi selezionare il corso prima di proseguire");
            return false; 
        }      
       var UtentiArray = [];
        $('#UtentiMig li').each(function(){
            UtentiArray.push($(this).attr('id')+";"+$(this).text());
        });
        var utenti=UtentiArray.join(); 
        $('#FormDuplicaIscritti').append( '<input type="hidden" name="Selezionati" value="'+utenti+'"/>' );
     });
     $("#UtentiAtt").sortable({ 
        connectWith: '#UtentiTra',		
        update: function(event, ui) {
            opts = {
                   url: ajaxurl, // ajaxurl is defined by WordPress and points to /wp-admin/admin-ajax.php
                   type: 'POST',
                   async: true,
                   cache: false,
                   dataType: 'json',
                   data:{
                           action: 'post_homeboxes_sort', // Tell WordPress how to handle this ajax request
                           order: $('#UtentiTra').sortable('toArray').toString() // Passes ID's of list items in	1,3,2 format
                   },
                   success: function(response) {
                           return; 
                   },
           };
            $.ajax(opts);
        }
    });
    $("#UtentiCur").sortable({ 
        connectWith: '#UtentiMig',		
        update: function(event, ui) {
            opts = {
                   url: ajaxurl, // ajaxurl is defined by WordPress and points to /wp-admin/admin-ajax.php
                   type: 'POST',
                   async: true,
                   cache: false,
                   dataType: 'json',
                   data:{
                           action: 'post_homeboxes_sort', // Tell WordPress how to handle this ajax request
                           order: $('#UtentiMig').sortable('toArray').toString() // Passes ID's of list items in	1,3,2 format
                   },
                   success: function(response) {
                           return; 
                   },
           };
            $.ajax(opts);
        }
    });
    $("#UtentiTra").sortable({ 
        connectWith: '#UtentiAtt',
        update: function(event, ui) {
            opts = {
                    url: ajaxurl, // ajaxurl is defined by WordPress and points to /wp-admin/admin-ajax.php
                    type: 'POST',
                    async: true,
                    cache: false,
                    dataType: 'json',
                    data:{
                            action: 'post_homeboxes_sort', // Tell WordPress how to handle this ajax request
                            order: $('#UtentiAtt').sortable('toArray').toString() // Passes ID's of list items in	1,3,2 format
                    },
                    success: function(response) {
                            return; 
                    },
            };
            $.ajax(opts);
        }
    });
    $("#UtentiMig").sortable({ 
        connectWith: '#UtentiCur',
        update: function(event, ui) {
            opts = {
                    url: ajaxurl, // ajaxurl is defined by WordPress and points to /wp-admin/admin-ajax.php
                    type: 'POST',
                    async: true,
                    cache: false,
                    dataType: 'json',
                    data:{
                            action: 'post_homeboxes_sort', // Tell WordPress how to handle this ajax request
                            order: $('#UtentiCur').sortable('toArray').toString() // Passes ID's of list items in	1,3,2 format
                    },
                    success: function(response) {
                            return; 
                    },
            };
            $.ajax(opts);
        }
    });
     $("#UtentiDisp").sortable({ 
        connectWith: '#UtentiAss, #UtentiAss2',		
        update: function(event, ui) {
            opts = {
                   url: ajaxurl, // ajaxurl is defined by WordPress and points to /wp-admin/admin-ajax.php
                   type: 'POST',
                   async: true,
                   cache: false,
                   dataType: 'json',
                   data:{
                           action: 'post_homeboxes_sort', // Tell WordPress how to handle this ajax request
                           order: $('#UtentiAss').sortable('toArray').toString() // Passes ID's of list items in	1,3,2 format
                   },
                   success: function(response) {
                           return; 
                   },
           };
            $.ajax(opts);
        }
    });
    $("#UtentiAss").sortable({ 
        connectWith: '#UtentiDisp, #UtentiAss2',
        update: function(event, ui) {
            opts = {
                    url: ajaxurl, // ajaxurl is defined by WordPress and points to /wp-admin/admin-ajax.php
                    type: 'POST',
                    async: true,
                    cache: false,
                    dataType: 'json',
                    data:{
                            action: 'post_homeboxes_sort', // Tell WordPress how to handle this ajax request
                            order: $('#UtentiAss').sortable('toArray').toString() // Passes ID's of list items in	1,3,2 format
                    },
                    success: function(response) {
                            return; 
                    },
            };
            $.ajax(opts);
        }
    });
     $("#ButtonSubmitFormatori").on("click",function(e){
        var DocentiArray = [];
        var TutorArray = [];
        $('div', '#Docenti').each(function(){
            DocentiArray.push($(this).attr('id')+";"+$(this).text());
        });
        var Formatori=DocentiArray.join(); 
        $('#FormatoriTutor').append( '<input type="hidden" name="Formatori" value="'+Formatori+'"/>' );
        $('div', '#Tutor').each(function(){
            TutorArray.push($(this).attr('id')+";"+$(this).text());
        });
        var Tutor=TutorArray.join(); 
        $('#FormatoriTutor').append( '<input type="hidden" name="Tutor" value="'+Tutor+'"/>' );
    });

    /* Sort container */
    $('.container').sortable({
    });	

    /* Sort step */
    $('.step').sortable({
            connectWith: '.step',
            items : ':not(.title)'
    });
        
});