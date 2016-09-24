
function authors(auth)
{
  for(i=0;i<auth.length;i++)
  {
    var p1 = parseFloat(auth[i][1]).toFixed(1).replace('.', ',');
    var cell = $("#usertable tbody tr td[data-user='" + auth[i][0].replace(/'/g, "\\'") + "']").next().next().next().next().next().next();
    if (cell.length > 0)
    {
      cell.html(p1 + "&#x202f;%");
      $('#usertable').trigger("updateCell",cell);
    }
  }
  //$('#usertable').trigger("update").trigger("sorton",[[[6,1]]]);
  $('#usertable').trigger("update").trigger("appendCache");
  $('#usertable').trigger("sorton",[[[6,1]]]);
  //setTimeout("$('#usertable').trigger('update').trigger('sorton',[[[6,1]]]);", 200);
  
  var data = [];
  var rest = 100;
  for(i=0;(i<auth.length) && (i<12);i++)
  {
    if (auth[i][1] > 1) { data.push({ label: auth[i][0],  data: auth[i][1]}); rest -= auth[i][1]; }
  }
  if (rest > 0) data.push({ label: "Sonstige", data: rest });
  $.plot($("#chart_authors"), data,
  {
    series: {
        pie: { 
            show: true
        }
    }
  });
  $('#chart_authors').show();
}
