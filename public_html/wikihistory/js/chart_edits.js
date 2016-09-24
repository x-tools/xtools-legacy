
function chart_edits_label(label, series) 
{
	return "<div style='font-size:10pt; text-align:center; padding:2px; color:black;'>" + label + "<br/>" + Math.round(series.percent) + "&thinsp;%</div>";
}

function chart_edits( anon, minor, total, names )
{
  var data = 
  [
		{ label: names[0],  data: [[1,anon]] , color:'#9bcd9b'},
		{ label: names[1],  data: [[1,minor]], color:'#9b9bff' },
		{ label: names[2],  data: [[1,total-anon-minor]], color:'#9b9b9b' }
  ];

  $.plot('#chart_edits', data, 
  {
    series: {
        pie: {
            show: true,
            radius: 1,
            label: {
                show: true,
                radius: 1,
                formatter: chart_edits_label,
                background: {
                    opacity: 0.8
                }
            }
        }
    },
    legend: {
        show: false
    }
  });
}
