<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/featuresDeclare.php';
require_once __DIR__ . '/NodeVisitor.php';


use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use Features\featureWrapper;
use NodeVisitor\NodeVisitor;
use PhpParser\PhpVersion;

class astBuilder
{
    public $parser;

    public function __construct()
    {
        $factory = new ParserFactory();
        $this->parser = $factory->createForVersion(PhpParser\PhpVersion::fromString('7.4'));
    }

    public function builder($code)
    {
        $feature = new featureWrapper();

        try {
            $stmts = $this->parser->parse($code);
        } catch (\Throwable $e) {
            $feature->ast_parse_error = 1;
            return $feature->toArray();
        }

        $traverser = new NodeTraverser();
        $visitor = new NodeVisitor($feature);
        $traverser->addVisitor($visitor);
        $traverser->traverse($stmts);

        $feature->ast_parse_error = 0;
        return $feature->toArray();
    }

}

?>