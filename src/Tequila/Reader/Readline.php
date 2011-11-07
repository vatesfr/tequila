<?php

class Tequila_Reader_Readline extends Tequila_Reader
{
	public function read(Tequila $tequila)
	{
		return readline();
	}
}
