<?php
namespace Sheldon;
class Line extends \Zend\Console\Prompt\Line
{
  public static function prompt()
  {
    stream_set_blocking(STDIN, 1);
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