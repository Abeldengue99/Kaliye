<?php
$c = file_get_contents('inclusoes/components/index_scripts.php');
$c = str_replace('<script>', '', $c);
$c = str_replace('</script>', '', $c);
$c = preg_replace('/<\?php.*?\?>/s', '1', $c);
file_put_contents('test_syntax.js', $c);
exec('node -c test_syntax.js 2>&1', $out, $ret);
echo implode(PHP_EOL, $out);
if($ret !== 0) exit(1);
echo "SUCCESS";
