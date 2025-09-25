<?php
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

$phpWord = new PhpWord();
$section = $phpWord->addSection();
$section->addText('Hello PHPWord');

$tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'phpword_test_' . uniqid() . '.docx';
$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save($tmp);

echo "OK\n$tmp\n";
