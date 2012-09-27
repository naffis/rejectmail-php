#!/usr/bin/perl -w
##############################################################################
# Name: mail_parser.pl
# Description: Parses incoming mail and drops it in the database.
#############################################################################

use strict;			# Always use strict!
use MIME::Parser;		# For parsing the emails
use MIME::Entity;		# For parsing the emails
use DBI;			# For database interface
use MIME::Base64;
use Image::Magick;

##
# Set up database

my $dbh = DBI->connect("DBI:mysql:email", "rejectmail", "rejectproj");
my $sth_email  = $dbh->prepare("INSERT INTO email 
			    	VALUES('',?,?,?,?,?,?,?,?,NOW())");
my $sth_attach = $dbh->prepare("INSERT INTO attachments 
			    	VALUES('',?,?,?,?,?)");
my $insertid;

##
# Set the part iterator

my $i = 0;

##
# Create the parser

my $parser = new MIME::Parser;
$parser->output_dir("/tmp");
my $entity = $parser->parse(\*STDIN);

##
# Get the header (and fields we need)

my $head      = $entity->head;
my $full_head = $head->original_text;
my $to        = $head->get("To") || '';
my $cc        = $head->get("Cc") || '';
my $from      = $head->get("From") || '';
my $subj      = $head->get("Subject") || '';
my $recv      = $head->get("Received");

##
# Chomp the necessary fields

chomp($to); chomp($cc); chomp($from); chomp($subj);

##
# Due to the lack of X-Envelope like headers, we need to make it ourselves

$recv =~ s/^.*for <?(.+)>?;.*$/$1/s;
my ($env_user, $env_domain);
$recv =~ s/>$//; # small hack ;)
if($recv =~ m/^([^\@]+)\@(.+)$/) {
   $env_user   = $1;
   $env_domain = $2;
}

##
# Dump the entity

dump_entity($entity);

##
# Purge all remaining files
$parser->filer->purge;

################################################################################
# sub: dump_entity
#   Dumps the seperate message parts

sub dump_entity {
   my $ent = shift; 		# Get the entity

   my @parts = $ent->parts;	# See if we have more than one part
   
   if (@parts) {        	# Multipart...
      map { dump_entity($_) } @parts;
   } 
   else {               	# Single part...
      $i++;			# Increment the part iterator
      if($i == 1) {			# "Body", so dump to MySQL
         $sth_email->execute(
			$env_user,
			$env_domain,
#			$to,
			$recv,
			$cc,
			$from,
			$subj,
			$ent->body_as_string,
			$full_head,
         );
      	 $insertid = $dbh->{'mysql_insertid'};
      } 
      else {				# "Attachment"      	  
      	  my $encoded = $ent->body_as_string;
		  
          $sth_attach->execute(
			$insertid,			
			$ent->head->mime_type,
			$ent->head->mime_encoding,
			$ent->bodyhandle->path,
         	        $ent->body_as_string
	 	  );
      }
   }
}
