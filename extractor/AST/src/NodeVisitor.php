<?php

namespace NodeVisitor;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/featuresDeclare.php';

use Features\featureWrapper;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\Node\Scalar;


class NodeVisitor extends NodeVisitorAbstract {
    private featureWrapper $features;
    private array $superglobal = ['_GET', '_POST', '_COOKIE', '_REQUEST', '_FILES', '_ENV', '_SERVER', '_SESSION', 'GLOBALS'];
    private array $decodeFunctions = ['base64_decode', 'gzinflate', 'gzuncompress', 'str_rot13'];
    private array $dangerFunctions = ['eval', 'assert', 'system', 'exec','shell_exec', 'passthru', 'popen', 'proc_open'];

    private function getFuncName(Node $node): ?string {
        if($node instanceof Expr\FuncCall){
            if($node->name instanceof Name){
                return strtolower($node->name->toString());
            }
        }
        return null;
    }

    public function __construct(featureWrapper $features){
        $this->features = $features;
    }

    public function enterNode(Node $node) {
        $this->handleExcecFeatures($node);
        $this->handleDecodeFeatures($node);
        $this->handleDynamicFeatures($node);
        $this->handleStructuralFeatures($node);
    }

    private function isSuperglobal(Node $node): bool {
        if($node instanceof Expr\ArrayDimFetch && $node->var instanceof Expr\Variable && is_string($node->var->name) && in_array($node->var->name, $this->superglobal)){
            return true;
        }
        return false;
    }

    private function isDecodeFunction(Node $node): bool {
        if($node instanceof Expr\FuncCall && $node->name instanceof Name){
            $fname = $this->getFuncName($node);
            return in_array($fname, $this->decodeFunctions);
        }
        return false;
    }


    private function computeDecodeDepth(Node $node):int{
        if(!$node instanceof Expr\FuncCall || !$this->isDecodeFunction($node)){
            return 0;
        }

        $maxChild = 0;

        foreach($node->args as $arg){
            $maxChild = max($maxChild, $this->computeDecodeDepth($arg->value));
        }
        return 1 + $maxChild;
    }

    private function handleExcecFeatures(Node $node) {
        #check for eval with superglobal argument (evalArgSuperglobal feature)
        if($node instanceof Expr\Eval_) {
            $arg = $node->expr;

            if($arg instanceof Expr\ArrayDimFetch && $arg->var instanceof Expr\Variable && in_array($arg->var->name, $this->superglobal)){
                $this->features->execFeatures->evalArgSuperglobal = true;
            }
        }

        #check for other dangerous functions with superglobal arguments (dangerFuncArgSuperglobal feature) 
        if($node instanceof Expr\FuncCall && $node->name instanceof Name){
            $fname = $this->getFuncName($node);
            if(in_array($fname, $this->dangerFunctions)){
                foreach($node->args as $arg){
                    if($this->isSuperglobal($arg->value)){
                        $this->features->execFeatures->dangerFuncArgSuperglobal = true;
                    }
           
                    if($arg->value instanceof Expr\FuncCall && $this->isDecodeFunction($arg->value)){
                        $this->features->execFeatures->decodeFuncArgToSink = true;
                        
                    }
                }
            }
        }
    }

    private function handleDecodeFeatures(Node $node) {

        #compute max decode chain depth (maxDecodeChainDepth feature)
        if($this->isDecodeFunction($node)){
            $depth = $this->computeDecodeDepth($node);
            $this->features->decodeFeatures->maxDecodeChainDepth = max($this->features->decodeFeatures->maxDecodeChainDepth, $depth);
        }

        #count decode function calls (decodeFuncCount feature)
        if($node instanceof Expr\FuncCall && $this->isDecodeFunction($node)){
            $this->features->decodeFeatures->decodeFuncCount++;
        }
    }

    private function handleDynamicFeatures(Node $node){
        #check for dynamic function calls (dynamicFuncCall feature)
        if($node instanceof Expr\FuncCall && $node->name instanceof Expr\Variable){
            $this->features->dynamicFeatures->dynamicFuncCallExists = true;
        }

        #check for dynamic includes (dynamicInc feature)
        if($node instanceof Expr\Include_){
            if(!($node->expr instanceof Scalar\String_)){
                $this->features->dynamicFeatures->dynamicIncExists = true;
            }
        }

        #check for variable variables (varExists feature)
        if($node instanceof Expr\Variable && $node->name instanceof Expr\Variable){
            $this->features->dynamicFeatures->varExists = true;
        }

    }

    private function handleStructuralFeatures(Node $node) {

        #class definition exists (classDefExists feature)
        if($node instanceof Stmt\Class_){
            $this->features->structuralFeatures->classDefExists = true;
        }

        #count function definitions (funcDefCount feature)
        if($node instanceof Stmt\Function_ || $node instanceof Stmt\ClassMethod){
            $this->features->structuralFeatures->funcDefCount++;
        }

        #count superglobal used as function argument (superglobalAsFuncArg feature)
        if($node instanceof Expr\FuncCall ){
            foreach($node->args as $arg){
                if($this->isSuperglobal($arg->value)){
                    $this->features->structuralFeatures->superglobalAsFuncArg++;
                }
            }
        }

        #count superglobal used in assignment (superglobalInAsgn feature)
        if($node instanceof Expr\Assign && $this->isSuperglobal($node->expr)){
            $this->features->structuralFeatures->superglobalInAsgn++;
        }
    }
       
}
?>