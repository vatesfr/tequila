<?php

/**
 * An instance of this class is able to split a string into multiple words.
 *
 * A word  is delimited by whitespaces  unless the whitespaces  are escaped with
 * the “\” character or the part containing whitespace is quoted.
 *
 * All of the following are considered as words:
 * - word_without_space
 * - word\ with\ spaces\ escaped
 * - "quoted word"
 * - "quoted word containg a \" "
 * - more" "complex\ word" with special \\ and \""
 *
 * The parser is able to parse multiple strings in the same session.
 *
 * @property-read array $words The words found during the parsing.
 */
final class Tequila_Parser
{
	public function __construct()
	{
		$this->reset();
	}

	public function __get($name)
	{
		switch ($name)
		{
		case 'is_complete':
			return !($this->_escaped || $this->_quoted);
		case 'words':
			$name = '_'.$name;
			return $this->$name;
		}

		throw new Tequila_Exception(
			'Getting incorrect property: '.__CLASS__.'::'.$name
		);
	}

	/**
	 * Parses a string into an array of words.
	 *
	 * If the parsing is incomplete, the  user can call this function again with
	 * another string which will complete it.
	 *
	 * @param string $string
	 *
	 * @return boolean Whether the parsing  is complete, i.e. quoted strings are
	 *                 properly closed.
	 */
	public function parse($string)
	{
		foreach (str_split($string) as $letter)
		{
			if ($this->_escaped)
			{
				$this->_escaped = false;
				$this->_word .= $letter;
				continue;
			}

			if ($letter === '\\')
			{
				$this->_escaped = true;
				continue;
			}

			if ($this->_quoted)
			{
				if ($letter === '"')
				{
					$this->_quoted = false;

					// Even if the current word is empty, it is meaningful.
					$this->_in_word = true;
				}
				else
				{
					$this->_word .= $letter;
				}
				continue;
			}

			if ($letter === '"')
			{
				$this->_quoted = true;
				continue;
			}

			if (($letter === ' ') || ($letter === "\n"))
			{
				$this->_push_word();
			}
			else
			{
				$this->_word .= $letter;
			}
		}

		if ($this->_escaped || $this->_quoted)
		{
			return false;
		}

		$this->_push_word();

		return true;
	}

	/**
	 * Resets the  internal state  of the  parser, i.e. to  start a  new parsing
	 * session.
	 */
	public function reset()
	{
		$this->_escaped = false;
		$this->_quoted  = false;
		$this->_in_word = false;
		$this->_word    = '';
		$this->_words   = array();
	}

	private
		$_escaped,
		$_quoted,
		$_in_word,
		$_word,
		$_words;

	/**
	 * If the current word is meaningful adds it to the list of words.
	 */
	private function _push_word()
	{
		if ($this->_in_word || ($this->_word !== ''))
		{
			$this->_words[] = $this->_word;
			$this->_word = '';

			$this->_in_word = false;
		}
	}
}
