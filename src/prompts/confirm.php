<?php
namespace Sheldon;
class Confirm extends \Zend\Console\Prompt\Confirm
{
  public static function prompt()
  {
    stream_set_blocking(STDIN, 1);
    $args     = func_get_args();
    $args[0]  = StreamWriter::rspace($args[0]);

    try
    {
      $result = call_user_func_array(array('parent', 'prompt'), func_get_args());
    }
    catch(\Exception $e)
    {
      stream_set_blocking(STDIN, 0);
      throw $e;
    }

    stream_set_blocking(STDIN, 0);
    return $result;
  }
}