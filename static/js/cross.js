if(show_idbox!="")
{

  var html=showdialog(show_idbox);
  $(html).modal({
      position: ['20',]
  });

}

$(document).ready(function(){

    $('#changersvp').click(function(e){

	$('#rsvp_options').show();
	$('#rsvp_submitted').hide();


    });

});
