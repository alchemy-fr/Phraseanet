<?php

require_once(__DIR__ . "/../../../lib/classes/graphik/Graph.php");
require_once(__DIR__ . "/../../../lib/classes/graphik/Pie.php");
require_once(__DIR__ . "/../../../lib/classes/http/request.php");

$request = http_request::getInstance();
$parm = $request->get_parms("value", 'legend', 'title');

$values = unserialize(urldecode($parm['value']));
$legend = unserialize(urldecode($parm['legend']));
$title = unserialize(urldecode($parm['title']));



$graph = new Graph(400, 400);
$graph->setAntiAliasing(FALSE);
$graph->border->hide();
$graph->title->set($title);
$graph->title->setFont(new TuffyBold(9));
$graph->title->setColor(new Color(255, 141, 28));


$plot = new Pie($values, Pie::EARTH);
$graph->setBackgroundColor(
        new Color(246, 242, 241)
);
$plot->setCenter(0.5, 0.4);
$plot->setSize(0.5, 0.5);
$plot->set3D(12);
$plot->setBorderColor(new black);
$plot->explode(array(0 => 10, 1 => 10, 2 => 10, 3 => 15, 4 => 15, 5 => 20, 6 => 20, 7 => 20, 8 => 20, 9 => 20));
$plot->setStartAngle(234);


$plot->legend->setModel(Legend::MODEL_BOTTOM);
$plot->setLegend($legend);
$plot->setLabelPosition(8);
$plot->label->setPadding(3, 3, 3, 3);
$plot->setAbsSize(200, 200);
$plot->label->setFont(new Tuffy(9));
$plot->legend->setPosition(0.5, 1.15);
$plot->legend->setColumns(2);

$graph->add($plot);
$graph->draw();
?>
