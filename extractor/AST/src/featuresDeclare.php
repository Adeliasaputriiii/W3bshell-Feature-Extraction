<?php

namespace Features;


class execFeatures{
    public bool $evalArgSuperglobal = false;
    public bool $dangerFuncArgSuperglobal = false;
    public bool $decodeFuncArgToSink = false;

}

class decodeFeatures{
    public int $decodeFuncCount = 0;
    public int $maxDecodeChainDepth = 0;
}

class dynamicFeatures{
    public bool $dynamicFuncCallExists = false;
    public bool $dynamicIncExists = false;
    public bool $varExists = false;
}

class structuralFeatures{
    public bool $classDefExists = false;
    public int $funcDefCount = 0;
    public int $superglobalAsFuncArg = 0;
    public int $superglobalInAsgn = 0;
}

class featureWrapper{
    public execFeatures $execFeatures;
    public decodeFeatures $decodeFeatures;
    public dynamicFeatures $dynamicFeatures;
    public structuralFeatures $structuralFeatures;
    public int $ast_parse_error = 0;

    public function __construct(){
        $this->execFeatures = new execFeatures();
        $this->decodeFeatures = new decodeFeatures();
        $this->dynamicFeatures = new dynamicFeatures();
        $this->structuralFeatures = new structuralFeatures();
    }

    public function toArray(): array {
        return array_merge(
           get_object_vars($this->execFeatures),
           get_object_vars($this->decodeFeatures),
           get_object_vars($this->dynamicFeatures),
           get_object_vars($this->structuralFeatures),
              ['ast_parse_error' => $this->ast_parse_error]  
        );
    }
}

?>
