<?php

require_once(__DIR__ . "/../../../lib/classes/graphik/Graph.class.php");
require_once(__DIR__ . "/../../../lib/classes/graphik/LinePlot.class.php");
require_once(__DIR__ . "/../../../lib/classes/http/request.class.php");

$request = http_request::getInstance();
$parm = $request->get_parms("value", 'legend', 'absc');

$values = unserialize(urldecode($parm['value']));
$legend = unserialize(urldecode($parm['legend']));
$absc = $parm['absc'];

$graph = new Graph(800, 300);
$graph->setAntiAliasing(FALSE);
$graph->border->hide();
if (isset($values["Heures"]))
  unset($values["Heures"]);

for ($i = 0; $i < sizeof($values); $i++)
{
  if ($values[$i] < 1)
    $values[$i] = (float) ($values[$i]);
  else
    $values[$i] = (int) ($values[$i]);
}

$x = $values;
$y = $legend;

$plot = new LinePlot($x);
$plot->grid->hide(true);

$plot->setSpace(4, 4, 10, 0);
$plot->setPadding(40, 15, 30, 50);


$plot->mark->setType(Mark::SQUARE);
$plot->mark->setSize(4);
$plot->mark->setFill(new Blue);
$plot->mark->border->show();

$plot->setColor(new Color(85, 85, 85));
$plot->setFillColor(new Color(180, 180, 180, 75));

$plot->label->set($x);
$plot->label->move(0, -10);
$plot->label->setFont(new Tuffy(6));
$plot->label->setAlign(NULL, Label::MIDDLE);


$plot->xAxis->setLabelText($y);
$plot->xAxis->label->setAngle(90);
$plot->xAxis->label->setFont(new Tuffy(7));
$plot->yAxis->title->set("Nombre de connexions");
$plot->yAxis->title->setFont(new TuffyBold(10));
$plot->yAxis->title->move(-10, 0);
$plot->yAxis->setTitleAlignment(Label::TOP);
$plot->xAxis->title->set($absc);
$plot->xAxis->title->setFont(new TuffyBold(10));
$plot->xAxis->title->move(0, 10);
$plot->xAxis->setTitleAlignment(Label::RIGHT);

$graph->add($plot);
$graph->draw();
?>
