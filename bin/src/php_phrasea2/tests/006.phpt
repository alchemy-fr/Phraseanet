--TEST--
Check for phrasea_uuid_create avalaibility
--SKIPIF--
<?php if (!function_exists("phrasea_uuid_create")) print "skip"; ?>
--FILE--
<?php 
echo "phrasea_uuid_create is avalaible !";
/*
	you can add regression tests for your extension here

  the output of your test code has to be equal to the
  text in the --EXPECT-- section below for the tests
  to pass, differences between the output and the
  expected text are interpreted as failure

	see php5/README.TESTING for further information on
  writing regression tests
*/
?>
--EXPECT--
phrasea_uuid_create is avalaible !
