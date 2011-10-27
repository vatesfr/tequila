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

	private function _complete()
	{
		return array();
	}
}
