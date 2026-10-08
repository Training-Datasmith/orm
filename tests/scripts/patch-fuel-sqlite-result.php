<?php

/**
 * Fuel PDO SQLite SELECT rowCount() is unreliable; patch the vendored core copy used by tests.
 */

$coreRoot = dirname(__DIR__).'/fuel/core';
$target = $coreRoot.'/classes/database/pdo/result.php';

if ( ! is_file($target))
{
	fwrite(STDERR, "patch-fuel-sqlite-result: missing {$target}\n");
	exit(0);
}

$contents = file_get_contents($target);
$marker = 'SQLite PDO rowCount()/fetch() are unreliable for SELECT; prefetch in tests';
if (strpos($contents, $marker) !== false)
{
	exit(0);
}

$search = "\t\t// Find the number of rows in the result\n\t\t\$this->_total_rows = \$this->_result->rowCount();\n\t}";
$replace = "\t\t// Find the number of rows in the result\n\t\t\$this->_total_rows = \$this->_result->rowCount();\n\n\t\t// SQLite PDO rowCount()/fetch() are unreliable for SELECT; prefetch in tests.\n\t\tif (preg_match('/^\\s*SELECT\\b/i', \$sql))\n\t\t{\n\t\t\tif (\$this->_as_object === false || \$this->_as_object === null)\n\t\t\t{\n\t\t\t\t\$this->_results = \$this->_result->fetchAll(\\PDO::FETCH_ASSOC);\n\t\t\t}\n\t\t\telseif (is_string(\$this->_as_object))\n\t\t\t{\n\t\t\t\t\$this->_results = \$this->_result->fetchAll(\\PDO::FETCH_CLASS, \$this->_as_object);\n\t\t\t}\n\t\t\telse\n\t\t\t{\n\t\t\t\t\$this->_results = \$this->_result->fetchAll(\\PDO::FETCH_OBJ);\n\t\t\t}\n\t\t\t\$this->_total_rows = count(\$this->_results);\n\t\t}\n\t}";

if (strpos($contents, $search) === false)
{
	fwrite(STDERR, "patch-fuel-sqlite-result: unexpected result.php contents\n");
	exit(1);
}

$contents = str_replace($search, $replace, $contents);

$searchNext = "\tpublic function next()\n\t{\n\t\tparent::next();\n\n\t\tif (\$this->_as_object === false)";
$replaceNext = "\tpublic function next()\n\t{\n\t\tparent::next();\n\n\t\tif (is_array(\$this->_results))\n\t\t{\n\t\t\t\$this->_row = array_key_exists(\$this->_current_row, \$this->_results)\n\t\t\t\t? \$this->_results[\$this->_current_row]\n\t\t\t\t: null;\n\t\t\t\$this->_sanitizate();\n\n\t\t\treturn \$this->_row;\n\t\t}\n\n\t\tif (\$this->_as_object === false)";

if (strpos($contents, $searchNext) === false)
{
	fwrite(STDERR, "patch-fuel-sqlite-result: unexpected next() in result.php\n");
	exit(1);
}

$contents = str_replace($searchNext, $replaceNext, $contents);
file_put_contents($target, $contents);
