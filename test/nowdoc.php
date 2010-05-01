<pre>
Subj: nowdoc
Example: <<<'Hi'
	NOWDOC content
Hi;
<hr />
Expected output:
passed
passed
passed
passed
<hr />
Output:
<?php
	require_once './testUtils.php';
	
	testStrict(
<<<'E1'
E1
, '');

	testStrict(
<<<'DEL'
 DEL
DELa
DEL
, ' DEL
DELa');

	testStrict(
<<<'E1_'
\
E1_
, '\\');

	testStrict(
<<<'EON'
'
EON
, '\'');

	testStrict(
<<<DEL1
<<<'DEL2'
DEL2
DEL1
, '<<<' . "'DEL2'" . '
DEL2');
?>
</pre>