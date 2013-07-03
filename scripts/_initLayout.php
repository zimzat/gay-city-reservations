<?php

require_once '../scripts/_init.php';
$application->bootstrap();

function layoutView()
{
	$content = ob_get_clean();

	$layout = Zend_Layout::getMvcInstance();
	$layout->content = $content;
	echo $layout->render();
}

register_shutdown_function('layoutView');
ob_start();

$view = Zend_Layout::getMvcInstance()->getView();
