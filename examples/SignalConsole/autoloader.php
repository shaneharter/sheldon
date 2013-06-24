<?php

$namespaces = array(
  'Sheldon\examples\SignalConsole\*'  => __DIR__,
  'Sheldon\*'                         => dirname(dirname(__DIR__)) . '/src/',
);

spl_autoload_register(function ($class) use ($namespaces) {

  $NAMESPACE_SEPARATOR = '\\';
  krsort($namespaces); // This is important because we want to match the most specific namespace rule possible

  $match = null;
  $wildcard = false;
  foreach($namespaces as $ns => $candidate)
  {
    if ($wildcard = ('\*' == substr($ns, -2)))
      $ns = substr($ns, 0, -2);
    elseif ('\\' == substr($ns, -1)) // Forgive an accidental trailing slash
    $ns = substr($ns, 0, -1);

    // Determine if this rule matches the class at all
    if (strpos($class, $ns . $NAMESPACE_SEPARATOR) !== 0)
      continue;

    $match = array(
      'namespace' => $ns,
      'path'      => $candidate,
      'wildcard'  => $wildcard
    );

    break;
  }

  if (empty($match))
    return false;

  $class_namespace = explode($NAMESPACE_SEPARATOR, $class);

  // If the matched rule has a wildcard at the end, we overlap the matched namespace with the current class namespace.
  // Given the rule:  Foo\Bar\*  and the class Foo\Bar\Baz\MyClass the wildcard indicates that the Foo\Bar are overlapped and removed
  // So the result will be merely Baz\MyClass, which will be turned into to the path /Baz/MyClass.php appended to the $match path
  if ($match['wildcard']) {
    $match_namespace = explode($NAMESPACE_SEPARATOR, $match['namespace']);
    $class_namespace = array_diff($class_namespace, $match_namespace);
  }

  $class = array_pop($class_namespace);
  $file  = sprintf('%s/%s/%s.php', $match['path'], implode(DIRECTORY_SEPARATOR, $class_namespace), $class);
  $file  = preg_replace(sprintf('#[%s]{2,}#', DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR, $file); // Collapse path//file into path/file

  if (file_exists($file))
  {
    require_once $file;
    return true;
  }

  return false;
});