

// Database Structure
CREATE TABLE 'webpage_details' (
'link' text NOT NULL,
 'title' text NOT NULL,
 'description' text NOT NULL,
 'internal_link' text NOT NULL,
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=latin1

<?php

require 'vendor/autoload.php';

use App\App;
use Scripts\Parse;


App::bind('config', require 'config.php');

Parse::parse();
