#!/usr/bin/perl
###################################
## K.I.S.S. Site Search Engine
## scripted by Alexander Moskalyuk
## http://www.moskalyuk.com
###################################


use CGI;

#this is the address of your main directory
#(directory where you want to start searching)
#on the Unix machine
$basedir="/www/rejectmail";

#This is the main URL of your site
$baseurl="http://www.rejectmail.com";

#Here's the list of all the files to be searched during the search
$fileinfo="fileinfo.txt";

#Provide header and footer for nicer output
$headerfile="header.txt";
$footerfile="footer.txt";

print "Content-type: text/html\n\n";

getString(); #get the CGI string
getFiles(); #get the list of files to scan
search(); #do it!
print_header(); #print the header
results(); #insert the results of the search
print_footer(); #print the footer

#########################################################################
sub getFiles
{
	open (FILEINFO, "<$fileinfo") || die "No such file";
 	my $counter=0;
   while (<FILEINFO>)
   {
   	chomp($_);
      push (@files, $_);#push every filename onto array
      $points[$counter++]=0;#no points assigned to the file originally
   }#end of while
   close (FILEINFO);
}#end of getFiles
#########################################################################
sub getString
{
	my	$a=new CGI;
	my $str=$a->param('string'); #just one parameter
 	$str= lc $str; #all to lowercase
	@string = split (/ /, $str); #split on whitespace
}
#########################################################################
sub search
{
	foreach $search_word (@string)
 	{
		my	$counter=0;
  		foreach $file_name (@files)
    	{
       	open (FILE, "<$basedir$file_name");
       	my @all_lines = <FILE>;
        	foreach $line (@all_lines)
         {
         	$line = lc $line; #convert to lowercase
        		++$points[$counter] if ($line =~ /$search_word/);
         }#end of foreach
         close (FILE);
         $counter++;
      }#end of foreach
   }#end of foreach
}#end of search
##########################################################################
sub results
{
	sumall();
 	print ("You searched for <b>@string</b><br>");
 	print ("Total of $sumall results<br>");
   while ($sumall != 0)
   {
   	my $max=0;
    	for ($x=0; $x<($#points+1); $x++)
	  	{
   		$max=$x if ($points[$x]>$points[$max]);
     	}
     	print "$points[$max]: <a href=\"$baseurl$files[$max]\"> $files[$max]</a><br>\n";
      $points[$max]=0;
      sumall();
	}#end of while
# print ("End of results");
}#end of results 
##########################################################################
sub sumall
{
	$sumall=0;
	foreach $x (@points)
 	{
  		$sumall+=$x;
   }
}
##########################################################################
sub print_header
{
	open (header, "<$headerfile") || return;
 	while (<header>)
  	{
   	print $_;
   }
   close header;
}
##########################################################################
sub print_footer
{
	open (header, "<$footerfile") || return;
 	while (<header>)
  	{
   	print $_;
   }
   close header;
}
##########################################################################

