<?php

/**
 * This parser splits a string into multiple entries.
 *
 * Entries can be either NULL or strings.
 *
 * There is two types of strings:
 * - interpolated  strings where  some sequences  have a  special  meanings (see
 *   “Escape sequences” list);
 * - raw strings.
 *
 * Interpolated strings can be:
 * - quoted with “"”;
 * - naked (non quoted).
 *
 * Escape sequences:
 * - \n: New line
 * - \r: Carriage return
 * - \t: Tabulation
 * - \\: Backslash itself
 * - \": Quote (only for quoted strings)
 * - \ : Space (only for naked strings)
 *
 * A raw string begins with a “%” followed by a delimiter character which can be
 * anything excepts alphanumeric, whitespace  and control characters. The string
 * ends with  the same  character except for  the opening  “(”, “[”, “{”  or “<”
 * where  it ends with  the closing  character (respectively  “)”, “]”,  “}” and
 * “>”). Please, note that matching pairs inside the string are ignored.
 *
 * Grammar:
 *
 *   cmdline = [ whitespaces ] { entry [ whitespaces ] } [ comment ]
 *   entry   = null | naked_str | quoted_str | raw_str
 *   comment = '#' *anything*
 *
 *   whitespaces  = regex(\s+)
 *   null         = regex(/null/i)
 *   naked_str    = *escaped sequence*
 *   quoted_str   = '"' *escaped sequence* '"'
 *   raw_str      = '%' start_delim characters end_delim
 *
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
 *
 * @todo Add comments support.
 * @todo Maybe add booleans (true/false/yes/no).
 */
final class Tequila_Parser
{
	/**
	 * Tries to parse a given string.
	 *
	 * @param string $s
	 *
	 * @return (string|null)[]|false Returns the found entries or false if the
	 *     parsing failed.
	 */
	public function parse($s)
	{
		$this->_s = $s;

		$this->_i = 0;
		$this->_n = strlen($this->_s);

		return $this->_cmdline();
	}

	//--------------------------------------

	private function _regex($re)
	{
		if (!preg_match($re.'A', $this->_s, $match, 0, $this->_i))
		{
			return false;
		}

		$this->_i += strlen($match[0]);
		return $match;
	}

	//--------------------------------------

	private function _cmdline()
	{
		$this->_whitespaces();

		$entries = array();
		while (($e = $this->_entry()) !== false)
		{
			$entries[] = $e;

			$this->_whitespaces();
		}

		$this->_comment();

		// Everything was not parsed.
		if ($this->_i < $this->_n)
		{
			return false;
		}

		return $entries;
	}

	private function _entry()
	{
		($e = $this->_null()) !== false
			or ($e = $this->_nakedStr()) !== false
			or ($e = $this->_quotedStr()) !== false
			or $e = $this->_rawStr();

		return $e;
	}

	private function _comment()
	{
		return (boolean) $this->_regex('/#.*/');
	}

	private function _whitespaces()
	{
		return (boolean) $this->_regex('/\\s+/');
	}

	private function _null()
	{
		if ($this->_regex('/null/i'))
		{
			return null;
		}

		return false;
	}

	private function _nakedStr()
	{
		if ($match = $this->_regex('/[^"%#](?:[^\\s\\\\]+|(?:\\\\.))*/'))
		{
			return $this->_parseString($match[0], ' ');
		}

		return false;
	}

	private function _quotedStr()
	{
		if ($match = $this->_regex('/"((?:[^"\\\\]+|(?:\\\\.))*)"/'))
		{
			return $this->_parseString($match[1], '"');
		}

		return false;
	}

	private function _rawStr()
	{
		// Save current position.
		$cursor = $this->_i;

		if (!($match = $this->_regex('/%([^[:alnum:][:cntrl:][:space:]])/')))
		{
			return false;
		}

		$pairs = array(
			'(' => ')',
			'[' => ']',
			'{' => '}',
			'<' => '>',
		);
		$sd = $match[1];
		$ed = preg_quote(isset($pairs[$sd]) ? $pairs[$sd] : $sd, '/');
		$sd = preg_quote($sd, '/');

		if ($match = $this->_regex('/((?:[^'.$sd.$ed.']+|'.$sd.'(?1)'.$ed.')*)'.$ed.'/'))
		{
			return $match[1];
		}

		// No match, restore position.
		$this->_i = $cursor;
		return false;
	}

	//--------------------------------------

	private function _parseString($str, $delimiter = null)
	{
		$codes = array(
			'n' => "\n",
			'r' => "\r",
			't' => "\t",
			'\\' => '\\',
			'"' => '"',
		);

		if ($delimiter)
		{
			$codes[$delimiter] = $delimiter;
		}

		$str = str_split($str);
		$result = array();
		$escaped = false;
		foreach ($str as $c)
		{
			if ($escaped)
			{
				$result[] = (isset($codes[$c]) ? $codes[$c] : '\\'.$c);
				$escaped = false;
			}
			elseif ($c === '\\')
			{
				$escaped = true;
			}
			else
			{
				$result[] = $c;
			}
		}

		return implode($result);
	}
}
