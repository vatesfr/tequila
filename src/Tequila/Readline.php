<?php

/**
 * This  version of  Tequila, based  on GNU  Readline, provides  advanced typing
 * features such as auto-completion.
 */
class Tequila_Readline extends Tequila
{
	public function __construct()
	{
		// Readline use  a global state, to  keep the unit  tests functioning we
		// need to manually discard the history.
		readline_clear_history();

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

	public function addToHistory($line)
	{
		readline_add_history($line);
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
