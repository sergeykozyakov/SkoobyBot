<?php
echo 'Пробую записать файл\n\n';

$myfile = fopen("testfile.txt", "w") or die("Unable to open file!");
$txt = "John Doe\n";
fwrite($myfile, $txt);
$txt = "Jane Doe\n";
fwrite($myfile, $txt);
fclose($myfile);

echo 'Пробую создать папку\n\n';
mkdir("testing") or die("Unable to create dir!");

echo 'Пробую читать файл\n\n';
$file = fopen("testfile.txt", "r") or die("Unable to open file!");
echo fgets($file);
fclose($file);

echo 'Пробую записать файл 2\n\n';
echo file_put_contents("test.txt","Hello World. Testing!");

echo 'Пробую читать файл 2\n\n';
echo file_get_contents("test.txt");