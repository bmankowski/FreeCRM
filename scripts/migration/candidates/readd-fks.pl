#!/usr/bin/env perl
use strict;
use warnings;

my $db = $ENV{DB} // 'freecrm';
my $fk_file = $ENV{FK_FILE} // '/tmp/fk-add.sql';

open my $fh, '<', $fk_file or die "cannot read $fk_file: $!";
local $/;
my $sql = <$fh>;
close $fh;

my @stmts = grep { /\S/ } map { s/^\s+|\s+$//gr } split /;/, $sql;
my $re = qr/ALTER\s+TABLE\s+`([^`]+)`\s+ADD\s+CONSTRAINT\s+`([^`]+)`\s+FOREIGN\s+KEY\s*\(([^)]+)\)\s+REFERENCES\s+`([^`]+)`\s*\(([^)]+)\)/i;

sub exec_sql {
	my ($stmt) = @_;
	my $out_file = '/tmp/readd-fks.out';
	unlink $out_file;
	# Use shell redirection to capture output. $stmt is coming from our FK DDL file.
	my $cmd = "mariadb -uroot -proot $db -e " . quotemeta($stmt) . " > $out_file 2>&1";
	my $rc = system($cmd);
	$rc = ($rc == -1) ? 127 : ($rc >> 8);
	my $out = '';
	if (open my $ofh, '<', $out_file) {
		local $/;
		$out = <$ofh> // '';
		close $ofh;
	}
	return ($rc, $out);
}

my ($added, $cleanups, $fail) = (0, 0, 0);
my @fails;

foreach my $s (@stmts) {
	next unless $s =~ $re;
	my ($child, $constraint, $childcols_raw, $parent, $parentcols_raw) = ($1, $2, $3, $4, $5);
	my @childcols = map { my $x = $_; $x =~ s/`//g; $x =~ s/^\s+|\s+$//g; $x } split /,/, $childcols_raw;
	my @parentcols = map { my $x = $_; $x =~ s/`//g; $x =~ s/^\s+|\s+$//g; $x } split /,/, $parentcols_raw;
	my $stmt = $s . ';';

	my ($rc, $out) = exec_sql($stmt);
	if ($rc == 0) { $added++; next; }

	if ($out =~ /errno: 121|Duplicate key on write or update|already exists/i) {
		exec_sql("ALTER TABLE `$child` DROP FOREIGN KEY `$constraint`;");
		($rc, $out) = exec_sql($stmt);
		if ($rc == 0) { $added++; next; }
	}

	if ($out =~ /ERROR 1452/i) {
		my @on;
		for (my $i = 0; $i < @childcols && $i < @parentcols; $i++) {
			push @on, "c.`$childcols[$i]` <=> p.`$parentcols[$i]`";
		}
		my $on_clause = join(' AND ', @on);
		my $null_check = "p.`$parentcols[0]` IS NULL";
		my @not_null = map { "c.`$_` IS NOT NULL" } @childcols;
		my $where = join(' AND ', ($null_check, @not_null));
		my $del = "DELETE c FROM `$child` c LEFT JOIN `$parent` p ON $on_clause WHERE $where;";

		my ($rcd, $outd) = exec_sql($del);
		if ($rcd != 0) {
			push @fails, "$child\t$constraint\tcleanup_failed\t$outd";
			$fail++; next;
		}
		$cleanups++;
		($rc, $out) = exec_sql($stmt);
		if ($rc == 0) { $added++; next; }
		push @fails, "$child\t$constraint\tadd_failed_after_cleanup\t$out";
		$fail++; next;
	}

	push @fails, "$child\t$constraint\tadd_failed\t$out";
	$fail++;
}

open my $fo, '>', '/tmp/fk-failures.txt';
print $fo join("\n", @fails), "\n" if @fails;
close $fo;

my ($rc_cnt, $out_cnt) = exec_sql("SELECT COUNT(*) AS fk_count FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND CONSTRAINT_TYPE='FOREIGN KEY';");
print "[fk] added=$added cleanups=$cleanups failures=$fail\n";
print $out_cnt;
if (@fails) {
	print "[fk] first 20 failures (see /tmp/fk-failures.txt for full)\n";
	for (my $i = 0; $i < @fails && $i < 20; $i++) {
		my ($t, $c, $k, $m) = split /\t/, $fails[$i], 4;
		print "- $t.$c ($k)\n";
	}
}

