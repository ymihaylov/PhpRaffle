$(document).ready(function() {
  Raffle.setup();

  $('body').keyup(function(e){
   if(e.keyCode == 32){
       // user has pressed space
       Raffle.on_choose();
   }
});
});

var Raffle = {
  contestants: new Array('One', 'Two', 'Three', 'Four'),
  winner: null,
  djurking: false,
    
  setup: function() {
    $('#chooseit').live('click', Raffle.on_choose);
    $.ajax({
      url: 'raffle.php',
      method: 'GET',
      dataType: 'json',
      data: { 'getRandom': 50 },
      success: function( data ) {
        if ( data.message )
        {
          $('#chooser h1').html(data.message);
          $('#chooseit').hide();
        }
        else
        {
          Raffle.contestants = data;
        }
      }
    });    
  },
  
  on_choose: function() {
    Raffle.insert_previous_winner_into_list();
    $('#chooser, #chooseit').hide();
    $('#chooser h1').html("Computing.....");
    // TODO: Show randomly drawn names every 0.3 sec?

    Raffle.djurking = true;
    $('#chooser').fadeIn();
    Raffle.shuffle();
    
    setTimeout('Raffle.pick_winner()', 3000);

    return false;
  },
  
  shuffle: function() {
    if ( Raffle.djurking )
    {
      var randomnumber = Math.floor(Math.random()*Raffle.contestants.length);
      $("#chooser h1").html( Raffle.contestants[ randomnumber ] );
      setTimeout( 'Raffle.shuffle();', 200 );
    }
  },

  pick_winner: function() {
    Raffle.djurking = false;
    $('#chooser').hide();
    // var randomnumber = Math.floor(Math.random()*Raffle.contestants.length);
    // Raffle.winner = Raffle.contestants.splice(randomnumber,1);

    $.ajax({
      url: 'raffle.php',
      method: 'GET',
      dataType: 'json',
      success: function( data ) {
        if ( data.message )
        {
          $('#chooser h1').html(data.message);
          $('#chooseit').hide();
        }
        else
        {
          Raffle.winner = data;
          Raffle.display_winner();
        }
      }
    });
    
  },
  
  insert_previous_winner_into_list: function() {
    if(Raffle.winner) {
      $('#winners ol').append("<li>" + Raffle.winner.name + "</li>");
    }
  },
  
  display_winner: function() {
    if ( typeof( Raffle.winner.award ) != 'undefined' )
    {
      var winner = Raffle.winner.name;
      // if ( Raffle.winner.email )
        // winner = winner + "<br/>(" + Raffle.winner.email + ")";

      winner = winner + "<br/><em>" + Raffle.winner.award + "</em>";
      $('#chooser h1').html(winner);
    }
    else
      $('#chooser h1').html(Raffle.winner.name + "!");

    $('#chooseit span').html("Let's see who's next!");
    $('#chooseit').show();
    $('#chooser').fadeIn();
  }


};
