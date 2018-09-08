$(document).ready(function() {
  Raffle.setup();

  $('body').keyup(function(e){
      if(e.keyCode == 32){
        // user has pressed Space!
        Raffle.on_choose();
      }
  });
});

var Raffle = {
  contestants: new Array(),
  winner: null,
  djurking: false,

  setup: function() {
    $('#chooseit').on('click', Raffle.on_choose);
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
    $('#chooser h1').removeClass('winner-text');
    $('#chooser h1').html("Computing.....");

    Raffle.djurking = true;
    $('#chooser').fadeIn(500);
    Raffle.shuffle();

    setTimeout('Raffle.pick_winner()', 1500);

    return false;
  },

  shuffle: function() {
    if ( Raffle.djurking )
    {
      var randomNumber = Math.floor(Math.random() * Raffle.contestants.length),
          displayNameOnShuffle = 'Shuffling...';

      if (Raffle.contestants[randomNumber]['Name'] !== undefined) {
        displayNameOnShuffle = Raffle.contestants[randomNumber]['Name'];
      }

      $("#chooser h1").html(displayNameOnShuffle);
      setTimeout('Raffle.shuffle();', 150);
    }
  },

  pick_winner: function() {
    Raffle.djurking = false;

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
          Raffle.winner = data.winner;
          Raffle.display_winner();
        }
      }
    });
  },

  insert_previous_winner_into_list: function() {
    if(Raffle.winner) {
      $('#winners ol').append("<li>" + Raffle.winner.Name + "</li>");
    }
  },

  display_winner: function() {
    if ( typeof( Raffle.winner.award ) != 'undefined' )
    {
      var winner = Raffle.winner.name;
      winner = winner + "<br/><em>" + Raffle.winner.award + "</em>";

      $('#chooser h1').html(winner);
      $('#chooser h1').addClass('winner-text');
    }
    else {
      $('#chooser h1').html(Raffle.winner.Name + "!");
      $('#chooser h1').addClass('winner-text');
    }

    $('#chooseit span').html("Let's see who's next!");
    $('#chooseit').show();
    $('#chooser').fadeIn();
  }
};
