<?php

	// Database connection //
	@mysql_connect("localhost","rejectmail","zXYPy2") or die ("Can not connect to given host");
	@mysql_select_db("email") or die ("Can not connect to database");


   $query = @mysql_query("select attachment_body from attachments where attachment_id=1");
   $image = @mysql_result($query,0,"image");
   $type =  "jpg";

   Header( "Content-type: $type");
   echo "displaying image";
   echo $image;

?>