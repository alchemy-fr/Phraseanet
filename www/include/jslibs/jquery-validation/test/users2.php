<?php
$request = trim(strtolower($_REQUEST['username']));
//sleep(1);
$users = array('asdf', 'Peter', 'Peter2', 'George');
$valid = 'true';
foreach ($users as $user) {
    if( strtolower($user) == $request )
        $valid = "\"$user is already taken, please try something else\"";
}
echo $valid;
