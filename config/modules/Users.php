<?php
/* {[The file is published on the basis of FreeCRM Public License that can be found in the following directory: licenses/License.html]} */
$CONFIG = [
	// Show information about logged user in footer
	'IS_VISIBLE_USER_INFO_FOOTER' => false,

	// Argon2id password hashing parameters. Defaults match PHP 8 defaults and
	// exceed the OWASP 2024 minimum. Tune downward only on memory-constrained
	// hosts; OWASP minimum is memory_cost=47104, time_cost=1, threads=1.
	'PASSWORD_ARGON2_MEMORY_COST' => 65536, // KiB (64 MiB)
	'PASSWORD_ARGON2_TIME_COST'   => 4,     // iterations
	'PASSWORD_ARGON2_THREADS'     => 1,     // parallelism
];
