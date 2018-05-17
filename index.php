<?php
require_once 'class_amount2words.php';
$class = new AMOUNT2WORDS();

$data = new \stdClass();
$data->number = 10.01;
$data->lang = 'ua';
$data->currency = 'UAH';
$data->decimal = true;
$data->decimal2String = false;
$data->textTransform = 2;
$data->codePage = 'UTF-8';

header('Content-Type: text/html; charset=utf-8');

echo $class->getString($data->number, $data->lang, $data->currency, $data->decimal, $data->decimal2String, $data->textTransform, $data->codePage);

exit;