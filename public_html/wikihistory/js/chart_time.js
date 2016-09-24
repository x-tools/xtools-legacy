function chart_time( period, showAnonymous, showMinor, showArticleSize, names, totaledits, minoredits, anonedits, articlesize_max, articlesize_avg, articlesize_min  )
{
  function DrawString(text, color, x, y, align)
  {
    c.drawText({
      fillStyle: color,
      x: x, y: y,
      fontSize: 9,
      fontFamily: "Open Sans, sans-serif",
      text: text,
      align: align,
      respectAlign : true
    }); 
  }
  function FillRectangle(color, x, y, width, height)
  {
    c.drawRect({
      fillStyle: color,
      x: x, y: y,
      width: width,
      height: height,
      fromCenter: false
    });
  }
  function DrawLine(color, x1, y1, x2, y2)
  {
   /* c.drawLine({
      strokeStyle: color,
      strokeWidth: 0.5,
      x1: x1, y1: y1,
      x2: x2, y2: y2
    });*/
    DrawLineWidth(color, x1, y1, x2, y2, 0.5);
  }
  function DrawLineWidth(color, x1, y1, x2, y2, width)
  {
    c.drawLine({
      strokeStyle: color,
      strokeWidth: width,
      x1: x1, y1: y1,
      x2: x2, y2: y2
    });
  }
  function FillEllipse(color, x, y, width, height)
  {
    c.drawEllipse({
      fillStyle: color,
      x: x, y: y,
      width: width, height: height
    });  
  }

  // ------ init stuff --------------------------------------------------------

  // get maximum number of edits
  var maxedits = 1;
  for (i=0;i<totaledits.length;i++) if (totaledits[i]>maxedits) maxedits = totaledits[i];
  var maxsize = 0;
  for (i=0;i<articlesize_max.length;i++) if (articlesize_max[i]>maxsize) maxsize = articlesize_max[i];

  // init canvas and get font height
  var c = $('<canvas/>');
  c.drawText({ layer: true, fillStyle:'#000', x: 20, y: 20, fontSize: 9, fontFamily: "Open Sans, sans-serif", text: "X", name: 'textheight'});
  smallFontHeight = c.measureText('textheight').height;
  c.clearCanvas();

  // init some values
  var legendWidth = 36;
  var legendBottomHeight = smallFontHeight + (showMinor ? smallFontHeight : 0) + (showAnonymous ? smallFontHeight : 0) + 3;
  var legendTopHeight = 2 * smallFontHeight;
  var WidthPerTimeElement = (period == 'year') ? 38 : 19;
  var NumberOfTimeElements = totaledits.length;

  // canvas size
  c.attr('height', $('#chart_time').height());
  c.attr('width', totaledits.length * WidthPerTimeElement + legendWidth);
  d = $('<div/>').attr('style', 'width:100%;overflow-x:auto;margin:0;padding:0;');
  $('#chart_time').html(d.html(c));
  
  // ------ mouse stuff -------------------------------------------------------
  var hoveredElement = -1;
  c.mousemove(function( event ) 
  { 
    var x = event.pageX - c.offset().left;
    var newHoveredElement = -1;
    if ((WidthPerTimeElement > 0) && (x > legendWidth)) newHoveredElement = Math.floor((x - legendWidth) / WidthPerTimeElement);
    
    if (newHoveredElement != hoveredElement)
    {
      hoveredElement = newHoveredElement;
      DrawHovered(); 
    }
  });
  c.mouseleave(function() { hoveredElement = -1; DrawHovered(); });
  
  var oldimage = null;
  function DrawHovered()
  {
    if (oldimage != null) { c.clearCanvas(); c.drawImage({ source: oldimage, x: 0, y: 0, fromCenter: false }); }
    if (hoveredElement >= 0)
    {
      c.drawRect({ fillStyle: "rgba(100,149,237,0.4)", x: legendWidth + hoveredElement * WidthPerTimeElement, y: 0,
        width: WidthPerTimeElement, height: c.height(),
        fromCenter: false,
        layer: true, name: "hoverBox",
      });
    }
  }

  // ------ Drawing of the graph ----------------------------------------------

  c.clearCanvas();
  // prepare background
  for (i=0;i<NumberOfTimeElements;i++)
  {
    color = "rgba(0,0,0,0)";
    if ((i%2) == 0) color = "rgba(255,255,255,0.6)";
          
    FillRectangle(color, legendWidth + i * WidthPerTimeElement, 0, WidthPerTimeElement, c.height() - legendBottomHeight);

    if (i == hoveredElement)
      FillRectangle("rgba(100,149,237,0.6)", legendWidth + i * WidthPerTimeElement, 0, WidthPerTimeElement, c.height());
  }

  if (showArticleSize)
  {
    var lastsize = 0;
    var lasti = 0;
    for (i=0;i<NumberOfTimeElements;i++)
    {
      var thissize = articlesize_avg[i];

      var x1 = ((lasti + 0.5) * WidthPerTimeElement) + legendWidth;
      var y1 = (c.height() - legendBottomHeight - (lastsize / maxsize) * (c.height() - legendBottomHeight - 3 - legendTopHeight));
      var x2 = ((i + 0.5) * WidthPerTimeElement) + legendWidth;
      var y2 = (c.height() - legendBottomHeight - (thissize / maxsize) * (c.height() - legendBottomHeight - 3 - legendTopHeight));

      if ((thissize > 0) && (totaledits[i] > 1))
      {
        var y_max = (c.height() - legendBottomHeight - (articlesize_max[i] / maxsize) * (c.height() - legendBottomHeight - 3 - legendTopHeight));
        DrawLineWidth("#800000", x2-3, y_max, x2+3, y_max,2)
        var y_min = (c.height() - legendBottomHeight - (articlesize_min[i] / maxsize) * (c.height() - legendBottomHeight - 3 - legendTopHeight));
        DrawLineWidth("#800000", x2-3, y_min, x2+3, y_min,2)

        DrawLine("#800000", x2, y_min, x2, y_max)
      }

      if ((i > 0) && (thissize > 0))
      {
        DrawLine("#800000", x1, y1, x2, y2);
        FillEllipse("#800000", x2, y2, 4, 4);
        if (lasti == 0) FillEllipse("#800000", x1, y1, 4, 4);
      }
      if (thissize > 0)
      {
        lastsize = thissize;
        lasti = i;
      }
    }
  }

  // draw edit bars
  var offset1 = showMinor ? 0.41 : 0.23;
  var offset2 = showAnonymous ? 0.23 : 0.23;
  var barwidth = (showMinor && showAnonymous) ? 0.54 : ((showMinor | showAnonymous) ? 0.675 : 0.9);
  for (i=0;i<NumberOfTimeElements;i++)
  {
    var x = (i * WidthPerTimeElement) + legendWidth;
    var height;
    if (showAnonymous)
    {
      height = (anonedits[i] / maxedits) * (c.height() - legendBottomHeight - 3 - legendTopHeight);
      FillRectangle("rgba(0,128,0,0.4)", x + offset1 * WidthPerTimeElement, c.height() - legendBottomHeight - height, barwidth * WidthPerTimeElement, height);
    }
    if (showMinor)
    {
      height = (minoredits[i] / maxedits) * (c.height() - legendBottomHeight - 3 - legendTopHeight);
      FillRectangle("rgba(0,0,255,0.4)", x + offset2 * WidthPerTimeElement, c.height() - legendBottomHeight - height, barwidth * WidthPerTimeElement, height);
    }
    height = (totaledits[i] / maxedits) * (c.height() - legendBottomHeight - 3 - legendTopHeight);
    FillRectangle("rgba(0,0,0,0.4)", x + 0.05 * WidthPerTimeElement, c.height() - legendBottomHeight - height, barwidth * WidthPerTimeElement, height);

    DrawString(totaledits[i], "#000000", x + WidthPerTimeElement / 2, c.height() - smallFontHeight / 2 - (showMinor ? smallFontHeight : 0) - (showAnonymous ? smallFontHeight : 0), "center");
    if (showMinor)
      DrawString(minoredits[i], "#0000FF", x + WidthPerTimeElement / 2, c.height() - smallFontHeight / 2 - (showAnonymous ? smallFontHeight : 0), "center");
    if (showAnonymous)
      DrawString(anonedits[i], "#008000", x + WidthPerTimeElement / 2, c.height() - smallFontHeight / 2, "center");
      
    // Legend (at top)
    DrawString(names[i], "#000000", x + WidthPerTimeElement / 2, smallFontHeight, "center");
  }

  // legend (left side)
  if (showAnonymous) DrawString("Anon", "#008000", 1, c.height() - smallFontHeight / 2, "left");
  if (showMinor) DrawString("Minor", "#0000FF", 1, c.height() - smallFontHeight / 2 - (showAnonymous ? smallFontHeight : 0), "left");
  DrawString("Edits", "#000", 1, c.height() - smallFontHeight / 2 - (showMinor ? smallFontHeight : 0) - (showAnonymous ? smallFontHeight : 0), "left" );
  DrawLine("#A9A9A9", 0, c.height() - legendBottomHeight, c.width(), c.height() - legendBottomHeight);
  
  oldimage = c.getCanvasImage();
}
