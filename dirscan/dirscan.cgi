#!/usr/bin/perl

$basedir="/www/rejectmail";

#This is the URL of your site, to which the references will be appended
$baseurl="http://www.rejectmail.com";

#This is the location of the text file with the list of files to search
$fileinfo="fileinfo.txt";

open (list, ">$fileinfo");

print<<html
Content-type: text/html\n\n
<!--#echo banner=""-->
html
;

work ("$basedir");
exclude();
print ("<br>Printed to: <b>$fileinfo</b><br>");
close (list);  	

sub work
{
	my $dirname = shift;
 	print "<hr><b>dirname<b>-$dirname: <br>";
	opendir (DIR, $dirname);
 	my @entries = readdir (DIR);
  	closedir (DIR);
   
   foreach $entry (@entries)
   {
   	next if $entry eq ".";
	   next if $entry eq "..";
    	work ("$dirname/$entry") if -d ("$dirname/$entry");
		my $temp_name = "$dirname/$entry";
  		$temp_name =~ s/$basedir//;
      print list ("$temp_name\n") if ((-f ("$dirname/$entry")) && ("$dirname/$entry" =~ /\.htm/));
   }
}
     
sub exclude
{
	open (list, "<$fileinfo");
 	my @names=<list>;
   close list;
   open (output, ">$fileinfo");
  	foreach $name (@names)
   {
#The exclusion list. Any directory you want to exclude
#should be added here as follows: next if $name =~ /\/dir_name/;

   	next if $name =~ /\/private/;

      print output ("$name");
      print ("$name<br>");
	}
 close output;
}
