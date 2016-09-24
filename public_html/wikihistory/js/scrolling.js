$(window).bind("load", function() 
{
  var articleHeaderTop = $('#article_header').offset().top;
  var articleHeaderHeight = $('#article_header').outerHeight();

  $('#article_header_container').css('height', articleHeaderHeight);
  $('#topheader').css('top', articleHeaderHeight);

  $(window).scroll( function()
  {
    var windowTop = $(window).scrollTop();
    if (articleHeaderTop < windowTop) 
    {
      if ($('#article_header').css('position') != 'fixed')
      {
        $('#article_header').css({ position: 'fixed', top: 0, 'background-image': "url('gfx/back2.jpg')", 'background-color': '#dcdcdc' });
        articleHeaderHeight = $('#article_header').outerHeight();
        $('#article_header_container').css('height', articleHeaderHeight);
        $('#topheader').css('top', articleHeaderHeight);
      }
      
      var h = null;
      $('#content h1').each(function() { 
        if ($(this).attr('id') != 'topheader')
        {
          if ($(this).offset().top < windowTop + articleHeaderHeight) { h = this; $(this).css('visibility','hidden'); } else { $(this).css('visibility','visible'); }
        } 
      });
      if (h != null)
      {
        $('#topheader').css('display', 'block');
        $('#topheader').html($(h).html());
      }
    }
    else 
    {
      $('#article_header').css({ position: 'static', 'background-color': 'rgba(255,255,255,0.8)', 'background-image': 'none'});
      $('#topheader').css('display', 'none');
      $('#content h1').each(function() { $(this).css('visibility','visible'); });
    }
  });
});

function updateui()
{
  articleHeaderHeight = $('#article_header').outerHeight();
  $('#article_header_container').css('height', articleHeaderHeight);
  $('#topheader').css('top', articleHeaderHeight);
}
