(function( $ ) {
	'use strict';
        function aggOreLezioneComplessive(){
            var numItems = $('.calendario').length;
            var LezInizio=0
            var LezFine=0
            var LezInizio2=0
            var LezFine2=0;
            var TotMinuti=0;
            for(var i=0;i<numItems;i++){
                var Inizio=$("#orainizio_"+i+" option:selected").val();
                var Fine=$("#orafine_"+i+" option:selected").val();
                var Inizio2=$("#orainizio2_"+i+" option:selected").val();
                var Fine2=$("#orafine2_"+i+" option:selected").val();
                $("#OreLezione_"+i+"").html(calcolaOreLezioneGiorno(Inizio,Fine,Inizio2,Fine2,"OreMinuti"));
                LezInizio=$("#orainizio_"+i+" option:selected").val();
                LezFine=$("#orafine_"+i+" option:selected").val();
                LezInizio2=$("#orainizio2_"+i+" option:selected").val();
                LezFine2=$("#orafine2_"+i+" option:selected").val();
                TotMinuti+=calcolaOreLezioneGiorno(LezInizio,LezFine,LezInizio2,LezFine2,"Min");
//                alert(LezInizio+" "+LezFine+" "+LezInizio2+" "+LezFine2+" "+TotMinuti);
            }
            var risultato=DaMinAOreMin(TotMinuti);
            var Stile="color:green;font-weight: bold;";
            if(risultato!=$("#ore_lezioni").val()){
                Stile="color:red;font-weight: bold;";
            }
            $("#OreTotaliLezioni").html("<span style=\""+Stile+"\">"+DaMinAOreMin(TotMinuti)+"</span>");
        }
	function convOreMinuti(Orario){
            if(typeof(Orario) == "string" ){
                var s = Orario.split(":");
                if(s.length != 2) {return 0;};
                var ore = parseInt(s[0], 10);
                var minuti = parseInt(s[1], 10);
            }else {return 0;};
            var MinutiTotali=(parseInt(ore, 10)*60)+parseInt(minuti, 10);
//            alert(ore+" "+minuti+ " "+ MinutiTotali);
            return MinutiTotali;
        }
        function DaMinAOreMin(Minuti){
            var Ore=0;
            var OreMinuti=0;
            Ore=Math.floor(Minuti / 60);
            OreMinuti=Minuti%60;
//            alert(Minuti+" "+Ore+" "+OreMinuti);
            return (Ore<10?"0"+Ore:Ore)+":"+(OreMinuti<10?"0"+OreMinuti:OreMinuti);
	}
        function calcolaOreLezioneGiorno(Inizio,Fine,Inizio2,Fine2,Ret){
            var minInizio=convOreMinuti((Inizio=="00:00"?"24:00":Inizio));
            var minFine=convOreMinuti((Fine=="00:00"?"24:00":Fine));
            var minInizio2=convOreMinuti((Inizio2=="00:00"?"24:00":Inizio2));
            var minFine2=convOreMinuti((Fine2=="00:00"?"24:00":Fine2));
            var errore="";
            if(minFine<minInizio){
                errore="<span style=\"color:red;\">Inizio>Fine (1&ordf; sessione)</span> ";
                minFine=minInizio=0;
            }
            if(minFine2<minInizio2){
                errore="<span style=\"color:red;\">Inizio>Fine (2&ordf; sessione)</span> ";
                minFine2=minInizio2=0;
            }            
//            alert(Inizio+" "+Fine+" "+Inizio2+" "+Fine2);
//            alert(minInizio+" "+minFine+" "+minInizio2+" "+minFine2);
            var minLezione=(minFine-minInizio)+(minFine2-minInizio2);
            return (Ret=="Min"?minLezione:errore+" "+DaMinAOreMin(minLezione));
        }
        $( document ).ready(function() {
            $("#FormCreaUtenti").show();
            $('#utility-tabs-container').tabs({ active: $('#SelectTab').val() });
            $( "#progressbar" ).progressbar({
                value: 0,
                complete: function( event, ui ) {
                    $.ajax({type: 'POST',
                        url: ajaxurl, 
                        data:{
                            action:'StatisticheTabellaCorso',
                            security:ajaxsec
                        },
                        success: function(risposta){
                            $("#Dati").html(risposta);
                         },                   
                        error: function(error) { 
                             $("#Dati").html(error);
                        }
                    });               
                }
            });
        });
        $(document).delegate('.calendario', 'focus', function(){
            $(this).datepicker({ dateFormat: "dd/mm/yy"});
        });
        $(document).delegate('.EliminaRigaAttivita', 'click', function(e){
            e.preventDefault();
            $(this).parent().remove();
        });
        $(document).delegate('.orario', 'change', function(e){
            e.preventDefault();
            var curItem=$(this).parent().attr("data");
            var Inizio=$("#orainizio_"+curItem+" option:selected").val();
            var Fine=$("#orafine_"+curItem+" option:selected").val();
            var Inizio2=$("#orainizio2_"+curItem+" option:selected").val();
            var Fine2=$("#orafine2_"+curItem+" option:selected").val();
            $("#OreLezione_"+curItem+"").html(calcolaOreLezioneGiorno(Inizio,Fine,Inizio2,Fine2,"OreMinuti"));
            var numItems = $('.calendario').length;
            var LezInizio=0
            var LezFine=0
            var LezInizio2=0
            var LezFine2=0;
            var TotMinuti=0;
            for(var i=0;i<numItems;i++){
                LezInizio=$("#orainizio_"+i+" option:selected").val();
                LezFine=$("#orafine_"+i+" option:selected").val();
                LezInizio2=$("#orainizio2_"+i+" option:selected").val();
                LezFine2=$("#orafine2_"+i+" option:selected").val();
                TotMinuti+=calcolaOreLezioneGiorno(LezInizio,LezFine,LezInizio2,LezFine2,"Min");
//                alert(LezInizio+" "+LezFine+" "+LezInizio2+" "+LezFine2+" "+TotMinuti);
            }
            var risultato=DaMinAOreMin(TotMinuti);
            var Stile="color:green;font-weight: bold;";
            if(risultato!=$("#ore_lezioni").val()){
                Stile="color:red;font-weight: bold;";
            }
            $("#OreTotaliLezioni").html("<span style=\""+Stile+"\">"+DaMinAOreMin(TotMinuti)+"</span>");
        });                
        $(document).delegate('#ore_lezioni', 'change', function(e){
            var Stile="color:green;font-weight: bold;";
            if($("#OreTotaliLezioni").text()!=$("#ore_lezioni").val()){
                Stile="color:red;font-weight: bold;";
            }
            $("#OreTotaliLezioni").html("<span style=\""+Stile+"\">"+$("#OreTotaliLezioni").text()+"</span>");
        });
        $(document).delegate('.CopiaOraInizio1', 'click', function(e){
            e.preventDefault();
            var numItems = $('.calendario').length;
            var curItem=$(this).attr("data");
            var curOra=$("#orainizio_"+curItem+" option:selected").val();
            for(var i=curItem;i<numItems;i++){
                $("#orainizio_"+i).val(curOra);
            }
            aggOreLezioneComplessive();
        });
        $(document).delegate('.CopiaOraFine1', 'click', function(e){
            e.preventDefault();
            var numItems = $('.calendario').length;
            var curItem=$(this).attr("data");
            var curOra=$("#orafine_"+curItem+" option:selected").val();
             for(var i=curItem;i<numItems;i++){
                $("#orafine_"+i).val(curOra);
            }
            aggOreLezioneComplessive();
        });
        $(document).delegate('.CopiaOraInizio2', 'click', function(e){
            e.preventDefault();
            var numItems = $('.calendario').length;
            var curItem=$(this).attr("data");
            var curOra=$("#orainizio2_"+curItem+" option:selected").val();
            for(var i=curItem;i<numItems;i++){
                $("#orainizio2_"+i).val(curOra);
            }
            aggOreLezioneComplessive();
        });
        $(document).delegate('.CopiaOraFine2', 'click', function(e){
            e.preventDefault();
            var numItems = $('.calendario').length;
            var curItem=$(this).attr("data");
            var curOra=$("#orafine2_"+curItem+" option:selected").val();
            for(var i=curItem;i<numItems;i++){
                $("#orafine2_"+i).val(curOra);
            }
            aggOreLezioneComplessive();
        });
        $(document).delegate('.EliminaRiga', 'click', function(e){
            e.preventDefault();
            $(this).parent().remove();
        });
        $(document).delegate('.EliminaDocente', 'click', function(e){
            e.preventDefault();
            $(this).parent().remove();
        });
        $(document).delegate('.EliminaTutor', 'click', function(e){
            e.preventDefault();
            $(this).parent().remove();
        });
        $(document).delegate('#AvviaStatistiche', 'click', function(){
//            var NumeroCorsi=$("#NumCorsi").val();
            var Corsi=$("#IDCorsi").val().split(";");
            $("#progressbar").progressbar({
                  value:  0,
                  max:   Corsi.length
              }); 
            $("#Dati").html("");
            for(var i=0;i<=Corsi.length;i++){
//                alert(Corsi[i]);
               $.ajax({type: 'POST',
                    url: ajaxurl, 
                    data:{
                        action:'StatisticheTitoloCorso',
                        idcorso:Corsi[i],
                        init:i,
                        security:ajaxsec
                    },
                    success: function(risposta){
                        $("#TitoloCorrente").html(risposta);
                        $("#progressbar").progressbar({
                             value:  $( "#progressbar" ).progressbar( "value" )+1
                         });                      
                        $("#Corrente").html($( "#progressbar" ).progressbar( "value" ));
                    },                   
                    error: function(error) { 
                        $("#Corrente").html(i);
                        $("#TitoloCorrente").html(error);
                    }
                }); 
            }
        });
        $(document).delegate('.em-bookings-approve,.em-bookings-reject,.em-bookings-unapprove,.em-bookings-delete', 'click', function(){
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

    $(document).delegate('.fa-cloud', 'click',function(){
        if($(this).attr('data-enabled')=="Si"){
            var curElem=$(this);
            var dati = $(this).attr('id').split("x");
            var OreOnLine=$(this).attr('data-OreOnLine');
            var Massimo= $(this).attr('data-MaxOre');
            $( "#sliderol" ).slider({
               value: OreOnLine,
               min: 0,
               max: Massimo,
               step: 1,
               slide: function( event, ui ) {
                 $( "#OreOnLine" ).val(ui.value);
               }
             });
            $( "#OreOnLine" ).val( OreOnLine );
            $('#dialog-form3').dialog({
                    resizable: false,
                    height:170,
                    modal: true,
                    width: 230,
                    title: "Ore riconosciute per attività",
                    buttons: {
                        "Memorizza": function() {
                            var OreOnLine=$("#OreOnLine").val();
                            $.ajax({type: "post",
                                 url: ajaxurl,
                                data: { security:ajaxsec,
                                          action: 'CorsoSetOreOnLine', 
                                          oreol:OreOnLine,
                                      idcorsista:dati[1],
                                            data:dati[2]
                                        },  
                                beforeSend: function() {
                                    $("#loading").fadeIn('fast');
                                }, 
                                success: function(html){
                                     $("#loading").fadeOut('fast');
                                     var str=dati[2];
                                     var Attivita=str.substr(str.length-2, 2);
//                                    if (Number($("#OreOnLine").val())<0){
                                        curElem.removeClass( "OreOnLine" );
                                        var elemOreCors=Attivita+dati[1];
                                        $("#"+elemOreCors).empty();
                                        $("#"+elemOreCors).text(Number($("#OreOnLine").val()));
//                                    }else{
//                                        curElem.addClass( "OreOnLine" );
//                                      }
                                    curElem.attr("title",OreOnLine);
                                    curElem.attr("data-OreOnLine",OreOnLine);
                                    if(OreOnLine==curElem.attr("data-maxore")){
                                        curElem.addClass( "OreOnLine" );
                                    }else{
                                        curElem.removeClass( "OreOnLine" );
                                    }
                                    $( "#dialog-form3" ).dialog( "close" );
                                },
                                error: function(error) { 
                                    $("#loading").fadeOut('fast');
                                    $("#TitoloForm").html("Gestione ore OnLine");
                                    $("#MsgForm").html("Non sono riuscito ad inserire le ore per l'attività!");
                                    $('#InfoForm').dialog({
                                        resizable: false,
                                        height:300,
                                        modal: true,
                                        title: "Gestione Corsi",
                                        buttons: {
                                            "Chiudi":function() { 
                                                $( "#InfoForm" ).dialog( "close" );
                                                 }
                                             }
                                    });
                                    $( "#dialog-form3").dialog( "close" );
                                }
                            })
                        },
                        "Annulla": function() {
                            $( "#dialog-form3" ).dialog( "close" );
                        }
                    }
            });
        }
    });

    $(document).delegate('.fa-clock', 'click',function(){
        if($(this).attr('data-enabled')=="Si"){
            var curElem=$(this);
            var dati = $(this).attr('id').split("x");
            var IDAssenza="#0x"+dati[1]+"x"+dati[2];
             if( $(IDAssenza).hasClass("Assente")){
                $("#TitoloForm").html("Gestione assenza in minuti");
                $("#MsgForm").html("Corsista Assente, non puoi impostare una ulteriore assenza in minuti!");
                $('#InfoForm').dialog({
                    resizable: false,
                    height:300,
                    modal: true,
                    title: "Gestione Corsi",
                    buttons: {
                        "Chiudi":function() { 
                            $( "#InfoForm" ).dialog( "close" );
                             }
                         }
                });
                return;
            }
            var AssMinCorrente=$(this).attr('data-AssenzaMin');
            var Massimo= $('#OreLez'+dati[2]).val();
            $( "#slider" ).slider({
               value: AssMinCorrente,
               min: 0,
               max: Massimo,
               step: 5,
               slide: function( event, ui ) {
                 $( "#AssenzaMin" ).val(ui.value);
               }
             });
         $( "#AssenzaMin" ).val( AssMinCorrente );
//            alert(dati[1]+" "+dati[2]);
            $('#dialog-form2').dialog({
                    resizable: false,
                    height:170,
                    modal: true,
                    width: 230,
                    title: "Minuti di Assenza",
                    buttons: {
                        "Memorizza": function() {
                        var AssMin=$("#AssenzaMin").val();
//                        alert(AssMin);
                          $.ajax({type: "post",
                                 url: ajaxurl,
                                data: { security:ajaxsec,
                                          action: 'CorsoSetAssenzaMinuti', 
                                          minass:AssMin,
                                      idcorsista:dati[1],
                                            data:dati[2]
                                        },  
                                beforeSend: function() {
                                    $("#loading").fadeIn('fast');
                                }, 
                                success: function(html){
                                    $("#loading").fadeOut('fast');
                                    if (Number($("#AssenzaMin").val())==0){
                                        curElem.removeClass( "AssenzaMin" );
                                    }else{
                                        curElem.addClass( "AssenzaMin" );
                                      }
                                    curElem.attr("title",AssMin);
                                    curElem.attr("data-AssenzaMin",AssMin);
                                    $( "#dialog-form2" ).dialog( "close" );
                                },
                                error: function(error) { 
                                    $("#loading").fadeOut('fast');
                                    $("#TitoloForm").html("Gestione assenza in minuti");
                                    $("#MsgForm").html("Non sono riuscito ad inserire l'assenza in minuti!");
                                    $('#InfoForm').dialog({
                                        resizable: false,
                                        height:300,
                                        modal: true,
                                        title: "Gestione Corsi",
                                        buttons: {
                                            "Chiudi":function() { 
                                                $( "#InfoForm" ).dialog( "close" );
                                                 }
                                             }
                                    });
                                    $( "#dialog-form2").dialog( "close" );
                                }
                            })
                        },
                        "Annulla": function() {
                            $( "#dialog-form2" ).dialog( "close" );
                        }
                    }
            });
        }
    });
    $(document).delegate('.Assente', 'click',function(){
        if($(this).attr('data-enabled')=="Si"){
            var curElem=$(this);
            var dati = $(this).attr('id').split("x");
            $.ajax({type: 'POST',
                url: ajaxurl, 
                data:{
                    security:ajaxsec,
                    action:'CorsoSetPresenza',
                    presenza:dati[0],
                    idcorsista:dati[1],
                    data:dati[2],
                },
                beforeSend: function() {
                    curElem.removeClass( "Assente" );
                    curElem.addClass( "fa-refresh fa-spin fa-fw" );
                },
                success: function(risposta){
                    curElem.removeClass( "fa-refresh fa-spin fa-fw" );
                    curElem.addClass( "Presente " );
                    $("#risultato").html(risposta);
                    $("#elaborazione").remove();
                },                   
                error: function(error) { 
                    curElem.removeClass( "fa fa-refresh fa-spin fa-2x fa-fw" );
                    curElem.addClass( "fa-user-o Assente" );
                    $("#TitoloForm").html("Gestione presenza");
                    $("#MsgForm").html("Non sono riuscito a segnare la Presenza");
                    $('#InfoForm').dialog({
                        resizable: false,
                        height:300,
                        modal: true,
                        title: "Gestione Corsi",
                        buttons: {
                            "Chiudi":function() { 
                                $( "#InfoForm" ).dialog( "close" );
                                 }
                             }
                    });
                    $("#elaborazione").remove();
                }
            }); 
        }
    });
    $(document).delegate('.Presente', 'click',function(){
        if($(this).attr('data-enabled')=="Si"){
           var curElem=$(this);
            var dati = $(this).attr('id').split("x");
            $.ajax({type: 'POST',
                url: ajaxurl, 
                data:{
                    security:ajaxsec,
                    action:'CorsoSetAssenza',
                    presenza:dati[0],
                    idcorsista:dati[1],
                    data:dati[2],
                },
                beforeSend: function() {
                    curElem.removeClass( "Presente" );
                    curElem.addClass( "fa-refresh fa-spin fa-fw" );
                },
                success: function(risposta){
                    curElem.removeClass( "fa-refresh fa-spin fa-fw" );
                    curElem.addClass( "Assente" );
                     $("#risultato").html(risposta);
                     $("#ElaborazioneExcel").hide();
                },                   
                error: function(error) { 
                    $("#TitoloForm").html("Gestione presenza");
                    $("#MsgForm").html("Non sono riuscito a segnare la Presenza");
                    $('#InfoForm').dialog({
                        resizable: false,
                        height:300,
                        modal: true,
                        title: "Gestione Corsi",
                        buttons: {
                            "Chiudi":function() { 
                                $( "#InfoForm" ).dialog( "close" );
                                 }
                             }
                    });
                    curElem.removeClass( "fa-refresh fa-spin fa-fw" );
                    curElem.addClass( "Presente" );
                }
            }); 
        }
    });    

    $(document).delegate('.fa-info-circle', 'click',function(){
        if($(this).attr('data-enabled')=="Si"){
            var curElem=$(this);
            var dati = $(this).attr('id').split("x");
            $("#note").val($(this).attr('data-Nota'));
            $('#dialog-form').dialog({
                    resizable: false,
                    height:230,
                    modal: true,
                    width: 440,
                    title: "Note Lezione",
                    buttons: {
                        "Memorizza": function() {
                        var Nota=$("#note").val();
                         $.ajax({type: "post",
                                 url: ajaxurl,
                                data: { security:ajaxsec,
                                          action: 'CorsoSetNote', 
                                            nota:Nota,
                                      idcorsista:dati[1],
                                            data:dati[2]
                                        },
                                beforeSend: function() {
                                    $("#loading").fadeIn('fast');
                                }, 
                                success: function(html){
                                    $("#loading").fadeOut('fast');
                                    if (!curElem.hasClass( "Nota" )){
                                      curElem.addClass( "Nota" );
                                    }
                                    curElem.attr("title",Nota);
                                    curElem.attr("data-nota",Nota);
                                    $( "#dialog-form" ).dialog( "close" );
                                    },
                                error: function(error) { 
                                    $("#loading").fadeOut('fast');
                                    $("#TitoloForm").html("Gestione note");
                                    $("#MsgForm").html("Non sono riuscito a memorizzare la Nota");
                                    $('#InfoForm').dialog({
                                        resizable: false,
                                        height:300,
                                        modal: true,
                                        title: "Gestione Corsi",
                                        buttons: {
                                            "Chiudi":function() { 
                                                $( "#InfoForm" ).dialog( "close" );
                                                 }
                                             }
                                    });
                                     $( "#dialog-form").dialog( "close" );
                                }
                            })
                        },
                        "Cancella": function() {
                         $.ajax({type: "post",
                                 url: ajaxurl,
                                data: { security:ajaxsec,
                                          action: 'CorsoSetNote', 
                                            nota:"",
                                      idcorsista:dati[1],
                                            data:dati[2]
                                        },
                                beforeSend: function() {
                                    $("#loading").fadeIn('fast');
                                }, 
                                success: function(html){
                                    $("#loading").fadeOut('fast');
                                     curElem.removeClass( "Nota" );
                                     curElem.removeAttr("title");
                                     curElem.removeAttr("data-nota");
                                     $( "#dialog-form" ).dialog( "close" );
                                    },
                                error: function(error) { 
                                    $("#loading").fadeOut('fast');
                                    $("#TitoloForm").html("Gestione note");
                                    $("#MsgForm").html("Non sono riuscito a memorizzare la Nota");
                                    $('#InfoForm').dialog({
                                        resizable: false,
                                        height:300,
                                        modal: true,
                                        title: "Gestione Corsi",
                                        buttons: {
                                            "Chiudi":function() { 
                                                $( "#InfoForm" ).dialog( "close" );
                                                 }
                                             }
                                    });
                                    $( "#dialog-form" ).dialog( "close" );
                                }
                            })
                        },
                        "Annulla": function() {
                            $( "#dialog-form" ).dialog( "close" );
                        }
                    }
            });
        }
    });


    $(function () {             
        $(".registro").click(function(e){
            var dati = $(this).attr('id').split(";");
            $("#DataLezione").val(dati[2]);
            $.ajax({timeout:0,
                    type: "post",
                     url: ajaxurl,
                    data: { security:ajaxsec,
                              action: 'ArgomentiLezione', 
                             idcorso:dati[1],
                                data:dati[2]
                            },
                    beforeSend: function() {
                       $("#loading").fadeIn('fast');
                    }, 
                    success: function(html){
                        //tinyMCE.activeEditor.setContent(html);
                        $("#argomenti").val(html);
                        $("#loading").fadeOut('fast');
                        },
                    complete: function( jqXHR, textStatus ){
                         $("#dialog-form-lezione").css("display", "block");
                        },    
                    error: function (xhr, ajaxOptions, thrownError) {
                        alert(xhr.status);
                        alert(thrownError);
                        
                        $("#loading").fadeOut('fast');
                        $("#TitoloForm").html("Gestione argomenti");
                        $("#MsgForm").html("Non sono riuscito a leggere gli argomenti della lezione");
                        $('#InfoForm').dialog({
                            resizable: false,
                            height:300,
                            modal: true,
                            title: "Gestione Corsi",
                            buttons: {
                                "Chiudi":function() { 
                                    $( "#InfoForm" ).dialog( "close" );
                                     }
                                 }
                        });
                       $("#dialog-form-lezione").css("display", "none");
                    }
                })            
/*            
            $("#DataLezione").val(dati[1]);
            var Argomenti=document.getElementById(dati[1]).value;
            tinyMCE.activeEditor.setContent(Argomenti);
            $("#dialog-form-lezione").css("display", "block");
*/        });
        $("#AnnullaArgomenti").click(function(e){
            $("#dialog-form-lezione").css("display", "none");
        });
        $("#MemorizzaArgomenti").click(function(e){
              $.ajax({type: "post",
                     url: ajaxurl,
                    data: { security:ajaxsec,
                              action: 'CorsoArgomentiLezione', 
                           argomenti:$('#argomenti').val(),
                             idcorso:$("#IDCorso").val(),
                                data:$("#DataLezione").val()
                            },
                    beforeSend: function() {
                        $("#loading").fadeIn('fast');
                    }, 
                    success: function(html){
                        $("#loading").fadeOut('fast');
                        $("#dialog-form-lezione").css("display", "none");
                        },
                    error: function(error) { 
                        $("#loading").fadeOut('fast');
                        $("#TitoloForm").html("Gestione argomenti");
                        $("#MsgForm").html("Non sono riuscito a memorizzare gli argomenti della lezione");
                        $('#InfoForm').dialog({
                            resizable: false,
                            height:300,
                            modal: true,
                            title: "Gestione Corsi",
                            buttons: {
                                "Chiudi":function() { 
                                    $( "#InfoForm" ).dialog( "close" );
                                     }
                                 }
                        });
                       $("#dialog-form-lezione").css("display", "none");
                    }
                })            
        });        
        $("#CancellaArgomenti").click(function(e){
              $.ajax({type: "post",
                     url: ajaxurl,
                    data: { security:ajaxsec,
                              action: 'CorsoArgomentiLezione', 
                           argomenti:"",
                             idcorso:$("#IDCorso").val(),
                                data:$("#DataLezione").val()
                            },
                    beforeSend: function() {
                        $("#loading").fadeIn('fast');
                    }, 
                    success: function(html){
                        $("#loading").fadeOut('fast');
                        $("#dialog-form-lezione").css("display", "none");
                        },
                    error: function(error) { 
                        $("#loading").fadeOut('fast');
                        $("#TitoloForm").html("Gestione argomenti");
                        $("#MsgForm").html("Non sono riuscito a cancellare gli argomenti della lezione");
                        $('#InfoForm').dialog({
                            resizable: false,
                            height:300,
                            modal: true,
                            title: "Gestione Corsi",
                            buttons: {
                                "Chiudi":function() { 
                                    $( "#InfoForm" ).dialog( "close" );
                                     }
                                 }
                        });
                       $("#dialog-form-lezione").css("display", "none");
                    }
                })            
        });                        
            $("#AddDocente").click(function(e){
                var ElencoDocFor=JSON.parse($("#ElementiListaDocTutor").val());
                var numItems = $('.docenti').length + 1;
                var ListaDocFor="";
                $.each(ElencoDocFor, function(i, val){
                    ListaDocFor=ListaDocFor.concat('<option value="', val.Id, '">',val.Cognome,' ',val.Nome,'</option>');
                });
                e.preventDefault();
                $("#docenti").append("<div id=\"Doce["+numItems+"]\" >\n\
                    <label><strong>Docente</strong></label> <select name=\"docente["+numItems+"]\" id=\"docente["+numItems+"]\" class=\"docenti\">"+ListaDocFor+"</select>  <a href=\"#\" class=\"EliminaDocente PulsDel\"><i class=\"fas fa-user-times\"></i></a>\n\
                     </div>");
            });            
            $("#AddTutor").click(function(e){
                var ElencoDocFor=JSON.parse($("#ElementiListaDocTutor").val());
                var numItems = $('.tutor').length + 1;
                var ListaDocFor="";
                $.each(ElencoDocFor, function(i, val){
                    ListaDocFor=ListaDocFor.concat('<option value="', val.Id, '">',val.Cognome,' ',val.Nome,'</option>');
                });
                e.preventDefault();
                $("#tutor").append("<div id=\"Tuto["+numItems+"]\" >\n\
                    <label><strong>Tutor</strong></label> <select name=\"tutor["+numItems+"]\" id=\"tutor["+numItems+"]\" class=\"tutor\">"+ListaDocFor+"</select>  <a href=\"#\" class=\"EliminaTutor PulsDel\"><i class=\"fas fa-user-times\"></i></a>\n\
                     </div>");
            });
            $("#AddData").click(function(e){
                var numItems = $('.calendario').length;
                var Ore=[{'1':'00:00'},{'2':'00:30'},{'3':'01:00'},{'4':'01:30'},{'5':'02:00'},{'6':'02:30'},{'7':'03:00'},{'8':'03:30'},{'9':'04:00'},{'10':'04:30'},{'11':'05:00'},{'12':'05:30'},{'13':'06:00'},{'14':'06:30'},{'15':'07:00'},{'16':'07:30'},{'17':'08:00'},
{'18':'08:30'},{'19':'09:00'},{'20':'09:30'},{'21':'10:00'},{'22':'10:30'},{'23':'11:00'},{'24':'11:30'},{'25':'12:00'},{'26':'12:30'},{'27':'13:00'},{'28':'13:30'},{'29':'14:00'},{'30':'14:30'},{'31':'15:00'},{'32':'15:30'},{'33':'16:00'},{'34':'16:30'},
{'35':'17:00'},{'36':'17:30'},{'37':'18:00'},{'38':'18:30'},{'39':'19:00'},{'40':'19:30'},{'41':'20:00'},{'42':'20:30'},{'43':'21:00'},{'44':'21:30'},{'45':'22:00'},{'46':'22:30'},{'47':'23:00'},{'48':'23:30'}];
                var ListaOre="";
                $.each(Ore, function(i, val){
                    ListaOre=ListaOre.concat('<option value="', val[i+1], '">',val[i+1],'</option>');
                });
                e.preventDefault();
                $("#date").append("<div id=\"Lezione["+numItems+"]\" >\n\
                    <blockquote  data=\""+numItems+"\">\n\
                    Data: <input class=\"calendario\" type=\"text\" id=\"datalezione["+numItems+"]\" name=\"datalezione["+numItems+"]\" size=\"8\">\n\
                    Inizio: <select class=\"orario\" name=\"orainizio["+numItems+"]\" id=\"orainizio_"+numItems+"\" >"+ListaOre+"</select> <a href=\"#\" class=\"CopiaOraInizio1 Puls\" title=\"Ricopia Ora Inizio Primo Blocco fino al fine della lista degli incontri\" data=\""+numItems+"\"> <i class=\"fas fa-clock\"></i></a>\n\
                    Fine: <select class=\"orario\" name=\"orafine["+numItems+"]\" id=\"orafine_"+numItems+"\" >"+ListaOre+"</select> <a href=\"#\" class=\"CopiaOraFine1 Puls\" title=\"Ricopia Ora Fine Primo Blocco fino al fine della lista degli incontri\" data=\""+numItems+"\"> <i class=\"fas fa-clock\"></i></a>\n\
                    Inizio: <select class=\"orario\" name=\"orainizio2["+numItems+"]\" id=\"orainizio2_"+numItems+"\" >"+ListaOre+"</select> <a href=\"#\" class=\"CopiaOraFine1 Puls\" title=\"Ricopia Ora Inizio Secondo Blocco fino al fine della lista degli incontri\" data=\""+numItems+"\"> <i class=\"fas fa-clock\"></i></a> \n\
                    Fine: <select class=\"orario\" name=\"orafine2["+numItems+"]\" id=\"orafine2_"+numItems+"\" >"+ListaOre+"</select> <a href=\"#\" class=\"CopiaOraFine2 Puls\" title=\"Ricopia Ora Fine Secondo Blocco fino al fine della lista degli incontri\" data=\""+numItems+"\"> <i class=\"fas fa-clock\"></i></a>\n\
                    <a href=\"#\" class=\"EliminaRiga PulsDel\"><i class=\"fas fa-calendar-times\"></i></a>\n\
                    Ore Lezione: <p id=\"OreLezione_"+numItems+"\" style=\"display:inline;font-weight: bold;\" data=\""+numItems+"\">00:00</p>\n\
                     </blockquote>\n\
                     </div>");
            });

            $("#AddAttivita").click(function(e){
                var numItems = $('.desatt').length;
                e.preventDefault();
                $("#attivita").append("<div id=\"Attivita["+numItems+"]\" >\n\
                    <blockquote>\n\
		    Attività: <input class=\"desatt\" type=\"text\" id=\"descrizione["+numItems+"]\" name=\"descrizione["+numItems+"]\" size=\"50\">\n\
                    Ore max riconosciute: <input type=\"number\" id=\"ore["+numItems+"]\" name=\"ore["+numItems+"]\" maxlength=\"3\" style=\"width:4em;\">\n\
                    Gestione individualizzata: <input type=\"checkbox\" value=\"Si\" id=\"individualizzata["+numItems+"]\" name=\"individualizzata["+numItems+"]\">\n\
		    <a href=\"#\" class=\"EliminaRigaAttivita PulsDel\"><i class=\"fas fa-trash\"></i></a>\n\
                    </blockquote>\n\
                  </div>");
            });
            $("#ElaborazioneExcel").hide();
            $("#ElaborazioneTabella").hide();
            function isValorizzato(Campo){
                Campo=Campo.toString();
                if (Campo.length===0) return false;
                else                 return true;
            }
            function isLunghezza(Campo,Min,Max){
                Campo=Campo.toString();
              if (Max===0){
                   if (Campo.length<Min ){
                       return false;
                   }else{
                       return true;
                   }
              }else{
                    if(Campo.length<Min && Campo.lenght>Max){
                        return false;
                    }else{
                        return true;
                    }    
              }
             }
            function isValidEmail(emailAddress) {
                var pattern = /^([a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+(\.[a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+)*|"((([ \t]*\r\n)?[ \t]+)?([\x01-\x08\x0b\x0c\x0e-\x1f\x7f\x21\x23-\x5b\x5d-\x7e\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|\\[\x01-\x09\x0b\x0c\x0d-\x7f\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))*(([ \t]*\r\n)?[ \t]+)?")@(([a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.)+([a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.?$/i;
                return pattern.test(emailAddress);
            };
            function generataScuole(elem){
                var Scuole=$('#CodiciScuole').val();
                var Codici= Scuole.split(";");
                for (var z = 0; z <= Codici.length-1; z++) { 
                    var Scuola= Codici[z].split(",");
                    elem.options[z] = new Option(Scuola[1], Scuola[0]);
                }
            }
        
    // Tabella Creazione Utenti        
            $( "#tabcreautentis" ).tabs({
                active:$("#CartellaAttiva").val()
            });    
    // Tabella Creazione Utenti        
            $( "#tabsnl" ).tabs(); 
    // Tabella Statistiche corso        
            $( "#tabstat" ).tabs(); 
    // Initialize appendGrid
            $('#GridUtenti').appendGrid({
                maxBodyHeight: 100,
                maintainScroll: true,
                caption: 'Utenti',
                initRows: 1,
                hideButtons: {
                    moveUp: true,
                    moveDown: true,
                    insert:true,
                },
                columns: [
                    { name: 'nome', display: 'Nome', type: 'text', ctrlAttr: { maxlength: 100 }, ctrlCss: { width: '150px'},
                      onChange: function (evt, rowIndex) {
                           if( !isLunghezza($("#GridUtenti_nome_"+(rowIndex+1)).val(),3,0)){
                               $("#GridUtenti_nome_"+(rowIndex+1)).focus();
                                alert("Il campo Nome deve contenere almeno 3 caratteri");
                            }
                        }},
                    { name: 'cognome', display: 'Cognome', type: 'text', ctrlAttr: { maxlength: 100 }, ctrlCss: { width: '150px'},
                      onChange: function (evt, rowIndex) {
                           if( !isLunghezza($("#GridUtenti_cognome_"+(rowIndex+1)).val(),3,0)){
                                $("#GridUtenti_cognome_"+(rowIndex+1)).focus();
                                alert("Il campo Cognome deve contenere almeno 3 caratteri");
                            }
                        }},
                    { name: 'email', display: 'Email', type: 'email', ctrlAttr: { maxlength: 100 }, ctrlCss: { width: '100px'},
                      onChange: function (evt, rowIndex) {
                           if( !isValidEmail($("#GridUtenti_email_"+(rowIndex+1)).val())){
                                $("#GridUtenti_email_"+(rowIndex+1)).focus();
                                alert("Il campo Email deve contenere un indirizzo email valido nel formato indirizzo@dominio.ext");
                            }
                        }},
                    { name: 'scuola', display: 'Scuola', type: 'select', ctrlOptions: generataScuole ,  ctrlCss: { width: '100px'} ,
                      onChange: function (evt, rowIndex) {
                           if( !isLunghezza($("#GridUtenti_scuola_"+(rowIndex+1)).val(),10,11)){
                                $("#GridUtenti_scuola_"+(rowIndex+1)).focus();
                                alert("Il campo Scuola deve contenere 10-11 caratteri");
                            }
                        }},
                    { name: 'codicefiscale', display: 'Codice Fiscale', type: 'text',ctrlAttr: { maxlength: 16 }, ctrlCss: { width: '150px'}  ,
                      onChange: function (evt, rowIndex) {
                           if( !isLunghezza($("#GridUtenti_codicefiscale_"+(rowIndex+1)).val(),16,16)){
                                $("#GridUtenti_codicefiscale_"+(rowIndex+1)).focus();
                                alert("Il campo Codice Fiscale deve contenere 16 caratteri");
                            }
                        }}
                 ]
            });

            // Handle `Serialize` button click
//            $('#btnSerialize').button().click(function () {
//               $('#divErrorPlacement').val($(document.forms['#ImportazioneUtenti']).serialize());
//           });
        $('#verificaDati').button().click(function () {
            $("#ElaborazioneTabella").show();
            $.ajax({type: 'POST',
                    url: ajaxurl, 
                    security:ajaxsec,
                    data:{
                        action:'VerificaNuoviUtenti',
                        valori:$(document.forms['0']).serialize()
                    },
                    beforeSend: function() {
                        $("#ElaborazioneExcel").show();
                    },
                    success: function(risposta){
                        $('#divRisultatoCreazione').empty().append(risposta);
                        if( $('#PassaggioSuccessivoL').val()=="Si"){
                              $("#creaUtenti").button( "enable" );
                        }
                        $("#ElaborazioneTabella").hide();  
                    },                   
                    error: function(error) { 
                        $('#divRisultatoCreazione').empty().append("Errore");
                        $("#ElaborazioneTabella").hide();
                    }

                }); 
            });
        $('#verificaDatiExcel').button().click(function () {
            $("#ElaborazioneExcel").show();
            $.ajax({type: 'POST',
                    url: ajaxurl, 
                    security:ajaxsec,
                    data:{
                        action:'VerificaNuoviUtentiExcel',
                        valori:$("#DatiExcelImportati").val()
                    },
                    beforeSend: function() {
                        $("#ElaborazioneExcel").show();
                    },
                    success: function(risposta){
                        $('#divRisultato').empty().append(risposta);
                        if( $('#PassaggioSuccessivo').val()=="Si"){
                              $("#creaUtentiExcel").button( "enable" );
                        }
                        $("#ElaborazioneExcel").hide();
                    },                   
                    error: function(error) { 
                        $('#divRisultato').empty().append("Errore");
                        $("#ElaborazioneExcel").hide();
                    }

                }); 
            });
        $('#creaUtentiExcel').button().click(function () {
            $("#ElaborazioneExcel").show();
            $.ajax({type: 'POST',
                    url: ajaxurl, 
                    security:ajaxsec,
                    data:{
                        action:'CreaNuoviUtentiExcel',
                        valori:$("#PSDati").val()
                    },
                    beforeSend: function() {
                        $("#ElaborazioneExcel").show();
                    },
                    success: function(risposta){
                        $('#divRisultato').empty().append(risposta);
                        $("#ElaborazioneExcel").hide();
                    },                   
                    error: function(error) { 
                        $('#divRisultato').empty().append("Errore");
                        $("#ElaborazioneExcel").hide();
                    }

                }); 
            });        
        $('#creaUtenti').button().click(function (){
           $("#ElaborazioneExcel").show();
           $.ajax({type: 'POST',
                    url: ajaxurl, 
                    data:{
                        action:'CreaNuoviUtenti',
                        valori:$("#PSDatiL").val()
                    },
                    beforeSend: function() {
                        $("#ElaborazioneExcel").show();
                    },
                    success: function(risposta){
                        $('#divRisultatoCreazione').empty().append(risposta);
                        $("#ElaborazioneExcel").hide();
                    },                   
                    error: function(error) { 
                        $('#divRisultatoCreazione').empty().append("Errore");
                        $("#ElaborazioneExcel").hide();
                    }

                }); 
            });        
    });
 
})( jQuery );
/* ========================================================================
 * Bootstrap: dropdown.js v3.3.7
 * http://getbootstrap.com/javascript/#dropdowns
 * ========================================================================
 * Copyright 2011-2016 Twitter, Inc.
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
 * ======================================================================== */


+function ($) {
  'use strict';

  // DROPDOWN CLASS DEFINITION
  // =========================

  var backdrop = '.dropdown-backdrop'
  var toggle   = '[data-toggle="dropdown"]'
  var Dropdown = function (element) {
    $(element).on('click.bs.dropdown', this.toggle)
  }

  Dropdown.VERSION = '3.3.7'

  function getParent($this) {
    var selector = $this.attr('data-target')

    if (!selector) {
      selector = $this.attr('href')
      selector = selector && /#[A-Za-z]/.test(selector) && selector.replace(/.*(?=#[^\s]*$)/, '') // strip for ie7
    }

    var $parent = selector && $(selector)

    return $parent && $parent.length ? $parent : $this.parent()
  }

  function clearMenus(e) {
    if (e && e.which === 3) return
    $(backdrop).remove()
    $(toggle).each(function () {
      var $this         = $(this)
      var $parent       = getParent($this)
      var relatedTarget = { relatedTarget: this }

      if (!$parent.hasClass('open')) return

      if (e && e.type == 'click' && /input|textarea/i.test(e.target.tagName) && $.contains($parent[0], e.target)) return

      $parent.trigger(e = $.Event('hide.bs.dropdown', relatedTarget))

      if (e.isDefaultPrevented()) return

      $this.attr('aria-expanded', 'false')
      $parent.removeClass('open').trigger($.Event('hidden.bs.dropdown', relatedTarget))
    })
  }

  Dropdown.prototype.toggle = function (e) {
    var $this = $(this)

    if ($this.is('.disabled, :disabled')) return

    var $parent  = getParent($this)
    var isActive = $parent.hasClass('open')

    clearMenus()

    if (!isActive) {
      if ('ontouchstart' in document.documentElement && !$parent.closest('.navbar-nav').length) {
        // if mobile we use a backdrop because click events don't delegate
        $(document.createElement('div'))
          .addClass('dropdown-backdrop')
          .insertAfter($(this))
          .on('click', clearMenus)
      }

      var relatedTarget = { relatedTarget: this }
      $parent.trigger(e = $.Event('show.bs.dropdown', relatedTarget))

      if (e.isDefaultPrevented()) return

      $this
        .trigger('focus')
        .attr('aria-expanded', 'true')

      $parent
        .toggleClass('open')
        .trigger($.Event('shown.bs.dropdown', relatedTarget))
    }

    return false
  }

  Dropdown.prototype.keydown = function (e) {
    if (!/(38|40|27|32)/.test(e.which) || /input|textarea/i.test(e.target.tagName)) return

    var $this = $(this)

    e.preventDefault()
    e.stopPropagation()

    if ($this.is('.disabled, :disabled')) return

    var $parent  = getParent($this)
    var isActive = $parent.hasClass('open')

    if (!isActive && e.which != 27 || isActive && e.which == 27) {
      if (e.which == 27) $parent.find(toggle).trigger('focus')
      return $this.trigger('click')
    }

    var desc = ' li:not(.disabled):visible a'
    var $items = $parent.find('.dropdown-menu' + desc)

    if (!$items.length) return

    var index = $items.index(e.target)

    if (e.which == 38 && index > 0)                 index--         // up
    if (e.which == 40 && index < $items.length - 1) index++         // down
    if (!~index)                                    index = 0

    $items.eq(index).trigger('focus')
  }


  // DROPDOWN PLUGIN DEFINITION
  // ==========================

  function Plugin(option) {
    return this.each(function () {
      var $this = $(this)
      var data  = $this.data('bs.dropdown')

      if (!data) $this.data('bs.dropdown', (data = new Dropdown(this)))
      if (typeof option == 'string') data[option].call($this)
    })
  }

  var old = $.fn.dropdown

  $.fn.dropdown             = Plugin
  $.fn.dropdown.Constructor = Dropdown


  // DROPDOWN NO CONFLICT
  // ====================

  $.fn.dropdown.noConflict = function () {
    $.fn.dropdown = old
    return this
  }


  // APPLY TO STANDARD DROPDOWN ELEMENTS
  // ===================================

  $(document)
    .on('click.bs.dropdown.data-api', clearMenus)
    .on('click.bs.dropdown.data-api', '.dropdown form', function (e) { e.stopPropagation() })
    .on('click.bs.dropdown.data-api', toggle, Dropdown.prototype.toggle)
    .on('keydown.bs.dropdown.data-api', toggle, Dropdown.prototype.keydown)
    .on('keydown.bs.dropdown.data-api', '.dropdown-menu', Dropdown.prototype.keydown)

}(jQuery);
jQuery(document).ready(function($){
    var custom_uploader;
    $('#logo_upload').click(function(e) {
        e.preventDefault();
        //If the uploader object has already been created, reopen the dialog
        if (custom_uploader) {
            custom_uploader.open();
            return;
        }
        //Extend the wp.media object
        custom_uploader = wp.media.frames.file_frame = wp.media({
            title: 'Scegli un logo',
            button: {
                text: 'Scegli un logo'
            },
            multiple: false
        });
        //When a file is selected, grab the URL and set it as the text field's value
        custom_uploader.on('select', function() {
            attachment = custom_uploader.state().get('selection').first().toJSON();
            $('#logo').val(attachment.url);
        });
        //Open the uploader dialog
        custom_uploader.open();
    });
});     