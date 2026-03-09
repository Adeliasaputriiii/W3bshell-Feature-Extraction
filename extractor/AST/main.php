<?php

require_once __DIR__ . '/vendor/autoload.php'; 

use PhpParser\ParserFactory;
use PhpParser\NodeDumper;

$code = '<?php $this->nama; $_GET["id"];';
$parser = (new ParserFactory())->createForNewestSupportedVersion();
$stmts = $parser->parse($code);

$dumper = new NodeDumper;
echo $dumper->dump($stmts);