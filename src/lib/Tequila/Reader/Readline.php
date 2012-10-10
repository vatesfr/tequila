<?php

/**
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
 *
 * @codeCoverageIgnore
 */
class Tequila_Reader_Readline extends Tequila_Reader
{
	public function read(Tequila $tequila, $prompt)
	{
		$result = readline($prompt);

		if (trim($result) !== '')
		{
			readline_add_history($result);
		}

		return $result;
	}
}
