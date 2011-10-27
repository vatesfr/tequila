<?php

/**
 * This  version of  Tequila, based  on GNU  Readline, provides  advanced typing
 * features such as auto-completion.
 */
class Tequila_Readline extends Tequila
{
	public function __construct()
	{
		parent::__construct();
		readline_completion_function(array($this, '_complete'));
	}

	public function __get($name)
	{
		switch ($name)
		{
		case 'history':
			return readline_list_history();
		}

		return parent::__get($name);
	}

	public function addToHistory($string)
	{
		readline_add_history($string);
	}

	public function clearHistory()
	{
		readline_clear_history();
	}

	public function prompt($prompt = '')
	{
		return readline($prompt);
	}

	// TODO: correct behaviour.
	private function _complete()
	{
		$info = readline_info();

		// Gets the substring in the line buffer up to the cursor position.
		$string = substr($info['line_buffer'], 0, $info['point']);

		$parser = new Tequila_Parser();
		$parser->parse($string);
		$words = $parser->words;
		$n = count($words);

		if (($n === 0) || ($n > 2))
		{
			return array();
		}

		$methods = $this->getAvailableMethods($this->getClass($words[0]));

		return $methods;
	}
}
