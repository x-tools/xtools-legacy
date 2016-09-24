function usertable_addrow(username, edits, minoredits, firstedit, lastedit)
{
  var p1 = parseFloat(minoredits / edits * 100).toFixed(1).replace('.', ',');
  
  
  var newrow = "<tr><td data-user='" + username.replace(/'/g, "&#39;") + "'><a href='//en.wikipedia.org/wiki/User:" + username.replace(/'/g, "&#39;") + "'>" + username.replace(/:/g, ":&#8203;") + "</a></td>";
  newrow += "<td>" + edits + "</td>";
  newrow += "<td>" + minoredits + "</td>";
  newrow += "<td>" + p1 + "&#x202f;%</td>";
  newrow += "<td>" + firstedit + "</td>";
  newrow += "<td" + ((lastedit == firstedit) ? " style='color:#B1B1B1;'" : "") + ">" + lastedit + "</td>";
  
  $('#usertable > tbody:last').append(newrow + "<td>?</td></tr>");
}


  $.tablesorter.addParser({ 
        id: 'p_parser', 
        is: function(s) { return s.substr(s.length-1,1) == "%"; }, 
        format: function(s) {
            return s.replace(/%/,'').replace(',','.'); 
        }, 
        type: 'numeric' 
    });
