<?php
include_once "Phrase.php";

$phrase = 'Ho can3 ûn g#4*Tt(_______) IN Cå$...aα Chë lîTiga sémpr€ CØL MIÕ Ç/-\n......€! AΑα';
$phrase = new \Tsuruya\Phrase($phrase, 'it_IT');
var_dump($phrase->getPhrase());
var_dump($phrase->filter());