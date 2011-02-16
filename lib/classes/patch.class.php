<?php
interface patch
{
	function get_release();
	
	function concern();
	
	function apply($id);
}