<?php


//Definition class
class Definition{
    public $filename;
    public $start_offset;
    public $end_offset;

    public function __construct($filename, $start_offset, $end_offset) {
        $this->filename = $filename;
        $this->start_offset = (int)$start_offset;
        $this->end_offset = (int)$end_offset;
    }
}

//Reference class
class Reference{
    public $filename;
    public $start_offset;
    public $end_offset;

    public function __construct($filename, $start_offset, $end_offset) {
        $this->filename = $filename;
        $this->start_offset = (int)$start_offset;
        $this->end_offset = (int)$end_offset;
    }
}


//Edge class
class Edge{
    public $definition;
    public $reference;

    public function __construct($definition, $reference) {
        $this->definition = $definition;
        $this->reference = $reference;
    }
}



//Phi class
class Phi{
    public $var_name;
    public $definition1;
    public $definition2;
    public function __construct($var_name, $definition1, $definition2) {
        $this->var_name = $var_name;
        $this->definition1 = $definition1;
        $this->definition2 = $definition2;
    }
}

//list all the files in a directory
function listDir($dir, $recursive = true, $basedir = '', $include_dirs = false) {
    if ($dir == '') {return array();} else {$results = array(); $subresults = array();}
    if (!is_dir($dir)) {$dir = dirname($dir);}
    if ($basedir == '') {$basedir = realpath($dir).DIRECTORY_SEPARATOR;}

    $files = scandir($dir);
    foreach ($files as $key => $value){
        if ( ($value != '.') && ($value != '..') && substr($value,0,1) != '.' ) {
            $path = realpath($dir.DIRECTORY_SEPARATOR.$value);
            if (is_dir($path)) {
                if ($include_dirs) {$subresults[] = str_replace($basedir, '', $path);}
                if ($recursive) {
                    $subdirresults = listDir($path, $recursive, $basedir, $include_dirs);
                    $results = array_merge($results, $subdirresults);
                }
            } else {
                $subresults[] = str_replace($basedir, '', $path);
            }
        }
    }
    if (count($subresults) > 0) {$results = array_merge($subresults, $results);}
    return $results;
}

//main
function isOperator($str) {
    $pattern='/^    [a-zA-Z_]+\[[0-9]+-[0-9]+\]$/';
    return preg_match($pattern, $str);
}

function hasPhi($str){
    $pattern = '/ = Phi\(/';
    return preg_match($pattern, $str);
}


function isVariable($str) {
    $pattern = '/Var#[0-9]+<\$[a-zA-Z0-9_]+\[/';
    return preg_match($pattern, $str);
}

function isPhiVariable($str) {
    $pattern = '/Var#[0-9]+<\$[a-zA-Z0-9_]+>/';
    return preg_match($pattern, $str);
}

function isAssignment($str){
    $pattern = '/^        var:/';
    return preg_match($pattern, $str);
}

function isNewBlock($str){
    $pattern = '/^Block#/';
    return preg_match($pattern, $str);
}


function isFunction($str){
    $pattern = '/^Function /';
    return preg_match($pattern, $str);
}

function findEdges($path){
    $files=listDir($path);
    foreach($files as $afile){
        $full_path=$path.$afile;
        // TODO fix the hard coded path below
        $output = shell_exec('php vendor/ircmaxell/php-cfg/generate_SSA.php '.$full_path);
        $tempfilename=$path."intermediate_output.txt";
        file_put_contents($tempfilename, $output);

        $definitions=array();
        $phis=array();
        $references=array();
        $edges=array();
        $function_name="main";
        if ($file = fopen($tempfilename, 'r')) {
            while (!feof($file)) {
                $line = fgets($file);
                if(isFunction($line)){
                    $pieces = explode( ' ', $line );
                    $function_name=substr($pieces[1],0,strpos($pieces[1], '('));
                }
                else if(isNewBlock($line)){
                    $current_block=substr($line,0,strlen($line)-1);
                }
                else if(isOperator($line)) {
                    $pos_open_bracket = strpos($line, '[');
                    $expr = substr($line, 4, $pos_open_bracket-4);
                    $pos_close_bracket = strpos($line, ']');
                    $l = $pos_close_bracket - $pos_open_bracket;
                    $str_offset = substr($line, $pos_open_bracket+1, $l-1);
                    $pieces = explode( '-', $str_offset );
                    $start = $pieces[0];
                    $end = $pieces[1];
                }

                else if (hasPhi($line)){
                    //key
                    $pos_start_var = strpos($line , '<$');
                    $pos_end_var = strpos($line , '>', $pos_start_var);
                    $var_name = substr($line, $pos_start_var+1, $pos_end_var-$pos_start_var-1);
                    $pos_key_start = strpos($line, "Var#");
                    $pos_key_end = strpos($line, "<", $pos_key_start);
                    $key = substr($line, $pos_key_start, $pos_key_end-$pos_key_start);

                    //def1
                    $pos_start_var_def1 = strpos($line , '<$' , $pos_end_var);
                    $pos_end_var_def1 = strpos($line , '[', $pos_start_var_def1);
                    $var_name_def1 = substr($line, $pos_start_var_def1+1, $pos_end_var_def1-$pos_start_var_def1-1);
                    $pos_key_start_def1 = strpos($line, "Var#", $pos_end_var);
                    $pos_key_end_def1 = strpos($line, "<", $pos_key_start_def1);
                    $key_def1 = substr($line, $pos_key_start_def1, $pos_key_end_def1-$pos_key_start_def1);

                    //def2
                    $pos_start_var_def2 = strpos($line , '<$' , $pos_end_var_def1);
                    $pos_end_var_def2 = strpos($line , '[', $pos_start_var_def2);
                    $var_name_def2 = substr($line, $pos_start_var_def2+1, $pos_end_var_def2-$pos_start_var_def2-1);
                    $pos_key_start_def2 = strpos($line, "Var#", $pos_end_var_def1);
                    $pos_key_end_def2 = strpos($line, "<", $pos_key_start_def2);
                    $key_def2 = substr($line, $pos_key_start_def2, $pos_key_end_def2-$pos_key_start_def2);
                    if(!array_key_exists($function_name,$references))
                        $references[$function_name] = array();

                    if(!array_key_exists($key,$references[$function_name])){
                        $phi = new Phi($var_name, $key_def1, $key_def2);
                        $phis[$function_name][$key]=$phi;
                    }
                }
                else if(isPhiVariable($line)){
                    $pos_start_var = strpos($line , '<$');
                    $pos_end_var = strpos($line , '>', $pos_start_var);
                    $var_name = substr($line, $pos_start_var+1, $pos_end_var-$pos_start_var-1);
                    $pos_key_start = strpos($line, "Var#");
                    $pos_key_end = strpos($line, "<", $pos_key_start);
                    $key = substr($line, $pos_key_start, $pos_key_end-$pos_key_start);
                    if(!array_key_exists($function_name,$references))
                        $references[$function_name] = array();

                    if(!array_key_exists($key,$references[$function_name])){
                        $array_for_curr_key=array();
                        
                        $ref = new Reference($afile, $start, $end);
                        array_push($array_for_curr_key,$ref);
                        $references[$function_name][$key] = $array_for_curr_key;
                    }
                    else {
                        $array_for_curr_key = $references[$function_name][$key];
                        $ref = new Reference($afile, $start, $end);
                        array_push($array_for_curr_key,$ref);
                        $references[$function_name][$key] = $array_for_curr_key;
                    }
                }
                else if(isVariable($line)){
                    $pos_start_var = strpos($line , '<$');
                    $pos_end_var = strpos($line , '[', $pos_start_var);
                    $var_name = substr($line, $pos_start_var+1, $pos_end_var-$pos_start_var-1);
                    $pos_key_start = strpos($line, "Var#");
                    $pos_key_end = strpos($line, "<", $pos_key_start);
                    $key = substr($line, $pos_key_start, $pos_key_end-$pos_key_start);
                    if(!array_key_exists($function_name,$definitions))
                        $definitions[$function_name] = array();
                    //var is a definition
                    if((isAssignment($line) && !array_key_exists($key,$definitions[$function_name])) || ($expr == "Expr_Param" && !array_key_exists($key,$definitions[$function_name]) )){
                        $pos_open_bracket = strpos($line, '[', $pos_key_end);
                        $pos_close_bracket = strpos($line, ']', $pos_open_bracket);
                        $l = $pos_close_bracket - $pos_open_bracket;
                        $str_offset = substr($line, $pos_open_bracket+1, $l-1);
                        $pieces = explode( '-', $str_offset);
                        $start = $pieces[0];
                        $end = $pieces[1];

                        $def = new Definition($afile, $start, $end);
                        $definitions[$function_name][$key] = $def;
                    }
                    else {
                        //var is a reference
                        if(!array_key_exists($function_name,$references))
                            $references[$function_name] = array();
                        if(!array_key_exists($key,$references[$function_name])){
                            $array_for_curr_key=array();
                            $ref = new Reference($afile, $start, $end);
                            array_push($array_for_curr_key,$ref);
                            $references[$function_name][$key] = $array_for_curr_key;
                        }
                        else {
                            $array_for_curr_key = $references[$function_name][$key];
                            $ref = new Reference($afile, $start, $end);
                            array_push($array_for_curr_key,$ref);
                            $references[$function_name][$key] = $array_for_curr_key;

                        }
                    }
                }
            }
        }

        fclose($file);
        unlink($tempfilename);

        if(!empty($definitions)) {
            foreach ($definitions as $key_array => $def_array) {
                foreach ($def_array as $key => $definition) {
                    if (!empty($references) && array_key_exists($key, $references[$key_array])) {
                        $corresponding_references = $references[$key_array][$key];
                        foreach ($corresponding_references as $reference) {
                            $edge = new Edge($definition, $reference);
                            array_push($edges, $edge);
                        }
                    }
                }
            }
        }


        if(!empty($phis)) {
            foreach ($phis as $key_array => $phi_array) {
                foreach ($phi_array as $key => $phi) {
                    if (!empty($references) && array_key_exists($key, $references[$key_array])) {
                        $corresponding_references = $references[$key_array][$key];
                        $corresponding_def1 = $definitions[$key_array][$phi->definition1];
                        $corresponding_def2 = $definitions[$key_array][$phi->definition2];
                        foreach ($corresponding_references as $reference) {
                            $edge = new Edge($corresponding_def1, $reference);
                            array_push($edges, $edge);
                            $edge = new Edge($corresponding_def2, $reference);
                            array_push($edges, $edge);
                        }
                    }
                }
            }
        }



        $final=array();

        if(!empty($definitions)) {
            foreach ($definitions as $key_array => $def_array) {
                foreach ($def_array as $key => $definition) {
                    if (!empty($references) && array_key_exists($key, $references[$key_array])) {
                        $temp = array();
                        $corresponding_references = $references[$key_array][$key];
                        foreach ($corresponding_references as $reference) {
                            array_push($temp, $reference);
                        }
                        $item=array();
                        $item = array("to"=>$temp, "from"=>$definition);
                        array_push($final,$item);
                    }
                }
            }
        }


        if(!empty($phis)) {
            foreach ($phis as $key_array => $phi_array) {
                foreach ($phi_array as $key => $phi) {
                    if (!empty($references) && array_key_exists($key, $references[$key_array])) {
                        $temp = array();
                        $corresponding_references = $references[$key_array][$key];
                        $corresponding_def1 = $definitions[$key_array][$phi->definition1];
                        $corresponding_def2 = $definitions[$key_array][$phi->definition2];
                        foreach ($corresponding_references as $reference) {
                            array_push($temp, $reference);
                        }
                        $item=array();
                        $item = array("to"=>$temp, "from"=>$corresponding_def1);
                        array_push($final,$item);
                        $item=array();
                        $item = array("to"=>$temp, "from"=>$corresponding_def2);
                        array_push($final,$item);

                    }
                }
            }
        }
        
        
        return $final;
    }//end foreach files as file
}
//$path = $argv[1];
//print_r(findEdges($path))
?>


