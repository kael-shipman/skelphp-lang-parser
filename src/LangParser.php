<?php
namespace Skel;

class LangParser implements \Skel\Interfaces\LangParser {
  public function getContainedArgs(string $str, string $delim=',', $i=0) {
    $openQuote = null;
    $inQuote = false;
    $argStart = null;
    $subCaptureStart = false;
    $args = array();
    while($i < strlen($str)) {
      // A non-escaped quote
      if (($str[$i] == '"' || $str[$i] == "'") && ($str[$i-1] != '\\' || $str[$i-2] == '\\')) {
        //echo "\n".$str[$i]." -- it's a quote";
        if ($inQuote === false) {
          //echo " - starting capture at ".($i+1);
          $inQuote = $str[$i];
          $argStart = $i;
          $i++;
          continue;
        }
        
        if ($inQuote == $str[$i]) {
          //echo " - matches the recorded opening quote; leaving quote";
          $inQuote = false;
        }

        $i++;
        continue;
      }

      // Any character that's not a quote, but is within a quote
      if ($inQuote !== false) {
        //echo "\n".$str[$i].' -- capturing a non-quote character within a quote';
        $i++;
        continue;
      }

      // A start parenthesis outside of a quote
      if ($str[$i] == '(') {
        //echo "\nOPENING PARENTHESIS FOUND";
        list($arg, $i) = $this->getContainedArgs($str, $delim, $i+1);
        $args[] = $arg;
        $subCaptureStart = true;
        continue;
      }

      // An end parenthesis outside of a quote - return a packet
      if ($str[$i] == ')') {
        //echo "\nCLOSING PARENTHESIS FOUND";
        if ($argStart !== null) {
          $args[] = $this->captureArgument($str, $argStart, $i-$argStart);
          //echo " -- captured `".$args[count($args)-1]."`";
        }
        return array($args, $i+1);
      }

      // A delimiter outside of a quote (capture the argument)
      if ($str[$i] == $delim) {
        //echo "\n  Delimiter found";
        if ($subCaptureStart) {
          $subCaptureStart = false;
          //echo " - ending subcapture";
          $i++;
          continue;
        }

        $arg = $this->captureArgument($str, $argStart, $i-$argStart);

        //echo " -- captured `".$arg."`";
        $args[] = $arg;
        $argStart = null;
        $i++;
        continue;
      }

      // White space outside a quote (don't do anything)
      if ($str[$i] == ' ' || $str[$i] == "\t") {
        //echo "\n(Ignoring whitespace outside a quote)";
        $i++;
        continue;
      }

      // Start capture if not already started
      if ($argStart === null) {
        //echo "\n".$str[$i]." -- beginning capture";
        $argStart = $i;
      } else {
        //echo "\n".$str[$i]." -- falling through";
      }

      $i++;
    }

    if ($argStart !== null) $args[] = $this->captureArgument($str, $argStart, $i-$argStart);

    return $args;
  }

  protected function captureArgument(string $str, int $argStart=null, int $argEnd=null) {
    $arg = trim(substr($str, $argStart, $argEnd), ' "\'');
    if (is_numeric($arg)) $arg = $arg*1;
    elseif ($arg == 'null') $arg = null;
    elseif ($arg == 'false') $arg = false;
    elseif ($arg == 'true') $arg = true;
    return $arg;
  }
}

