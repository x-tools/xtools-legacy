<?php

error_reporting(E_ERROR);
ini_set("display_errors", 1);

include ("../common/jpgraph/jpgraph.php");
include ("../common/jpgraph/jpgraph_pie.php");
include ("../common/jpgraph/jpgraph_pie3d.php");

$data = unserialize($_GET['pcts']);

$graph = new PieGraph(600,300,"auto");
$graph->SetShadow();

$graph->title->Set("Namespace totals");
$graph->title->SetFont(FF_FONT1,FS_BOLD);

$p1 = new PiePlot3D($data);
$p1->SetSize(0.5);
$p1->SetCenter(0.35);
$p1->SetLegends(unserialize($_GET['nsnames']));
$p1->SetSliceColors(unserialize($_GET['colors'])); 

$graph->Add($p1);
$graph->Stroke();

?>