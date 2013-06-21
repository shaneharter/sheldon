<?php
namespace Sheldon;

/**
 * Built on top of the Zend Prompt base. Ask the user an open-ended question.
 * Has default answers, regex validation, etc
 * @example Shell\Question::prompt('What is your favorite color?');
 * Class Question
 */
class Question extends \Zend\Console\Prompt\AbstractPrompt
{

  public $question;
  public $default;
  public $allowEmpty;
  public $style;
  public $pattern;
  public $prompt;

  /**
   * Ask the user a question with an optional default value
   *
   * @param string $question
   * @param null $default
   * @param bool $allowEmpty
   * @param string $style
   * @param null $mask
   */
  public function __construct($question, $default = null, $allowEmpty = false, $mask = null, $prompt = ':', $style = 'question')
  {
    $this->question   = $question;
    $this->default    = $default;
    $this->allowEmpty = $allowEmpty;
    $this->style      = $style;
    $this->pattern    = $mask;
    $this->prompt     = $prompt;

    if ($default && $mask && preg_match($mask, $default) == 0)
    {
      throw new \InvalidArgumentException("Cannot display question prompt: Given default '{$default}' does not match the supplied mask.");
    }
  }

  /**
   * All the built-in Zend Prompts include this prompt() static constructor
   * @return mixed
   */
  public static function prompt($question, $default = null, $allowEmpty = false, $mask = null, $prompt = ':', $style = 'question')
  {
    stream_set_blocking(STDIN, 1);
    $result = call_user_func_array(array('parent', 'prompt'), func_get_args());
    stream_set_blocking(STDIN, 0);
    return $result;
  }

  /**
   * Show the prompt to user and return the answer.
   *
   * @return mixed
   */
  public function show()
  {
    $question = $this->question;
    $default = (!empty($this->default)) ? " [{$this->default}]" : (($this->allowEmpty) ? ' (optional)' : '');

    if ($this->style)
    {
      $question = "<{$this->style}>{$question}</{$this->style}>";
    }

    if ($default)
    {
      $default = "<optional>{$default}</optional>";
    }

    $prompt = sprintf('%s%s%s ', $question, $default, $this->prompt);
    $writer = Application::instance()->write;

    do
    {
      $writer($prompt);
      if ($answer = $this->getConsole()->readLine($this->maxLength))
      {
        $valid = (!$this->pattern || preg_match($this->pattern, $answer) == 1);
        if (!$valid)
        {
          $writer('<error>Oops! Invalid answer. Please try again!</error>')->eol();
        }
      }
    } while ((!$answer && !$this->allowEmpty && !$this->default) || ($answer && !$valid));

    if (!$answer && $this->default)
    {
      $answer = $this->default;
    }

    if (is_numeric($answer))
    {
      if (strpos($answer, '.') === false)
      {
        $answer = intval($answer);
      } else {
        $answer = floatval($answer);
      }
    }

    return $this->lastResponse = $answer;
  }
}
