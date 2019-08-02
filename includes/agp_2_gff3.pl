
use warnings;
use strict;

# Purpose: convert agp file to gff3 like file, so we can use tabix to index it and fast access each line
# Arguements:
# 1. Input agp file
# 2. Output gff3 like File
# 3. sort and compress the gff3 file
# 4. index compressed file with tabix

my $file_in = $ARGV[0];
my $file_out = $ARGV[0].'.gff';

open (my $IN,"<", $file_in) or die ("Could not open file: $file_in .\n");
open (my $OUT, ">", $file_out) or die("Could not open output file.\n");

#do { my $header = <$IN> } until $. == 1 or eof; # Skips the first line
#print $OUT 'seqid',"\t",'source',"\t",'type',"\t",'start',"\t",'end',"\t",'score',"\t",'strand',"\t",'phase',"\t",'attribute',"\n";

# include header from agp to new file
print $OUT '#GFF3 like agp file for using tabix', "\n";
while (defined (my $line = <$IN>)){
	chomp $line;
	if ($line =~ /^#/){
		print $OUT $line, "\n";
	}
	else{
		my @one_line = split(/\t/,$line);
		if ( ($one_line[6] =~ /^[0-9,.E]+$/) && ($one_line[7] =~ /^[0-9,.E]+$/) ){
			my $attributes = join ';', @one_line;
			print $OUT $one_line[5],"\t",'.',"\t",'.',"\t",$one_line[6],"\t",$one_line[7],"\t",'.',"\t",$one_line[8],"\t",'.',"\t",$attributes,"\n";
		}

	}
}
close($IN);
close($OUT);

$command = '(grep ^"#" ' . $file_out .'; grep -v ^"#" ' . $file_out . ' | sort -k1,1 -k4,4n) | bgzip > ' . $file_in . '.sorted.gff.gz';
print 'Command for sort and compress: ', $command , " \n";
system($command);

$command = 'tabix -p gff ' . $file_in . '.sorted.gff.gz';
print 'Command for indexing: ', $command, " \n";
system($command);

exit();
