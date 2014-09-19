<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="ben@netcap.fr">
    <title>SeafileAPI to PHP</title>

    <!-- Bootstrap core CSS -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
	<style>
		body {
			padding-top: 70px;
		}
	</style>
    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>

  <body>

    <!-- Fixed navbar -->
    <div class="navbar navbar-default navbar-fixed-top" role="navigation">
      <div class="container">
        <div class="navbar-header">
          <a class="navbar-brand" href="#">Seafile API to PHP</a>
        </div>
      </div>
    </div>

    <div class="container">
		<?php
		error_reporting(E_ALL);
		ini_set('display_errors', 1);

		define('EMAIL', '');
		define('PASS', '');
		define('CLOUD_URL', '');
		define('CLOUD_PORT', '');

		require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'SeafileAPI.php');
		$seafile = new SeafileAPI(array(
			'url'		=> CLOUD_URL,
			'port'		=> CLOUD_PORT,
			'user'		=> EMAIL,
			'password'	=> PASS
		));
		/*
		*	Ping Seafile server
		*/
		echo '<h1>Ping</h1>';
		$seafile->debug($seafile->ping());

		/*
		*	Retrieve account informations
		*/
		echo '<h1>User account infos</h1>';
		$account_info = $seafile->checkAccountInfo();
		$seafile->debug($account_info);


		/*
		*	Get user libraries
		*/
		$libraries = $seafile->listLibraries();
		if($libraries){

			echo '<h1>All user libraries</h1>';
			$seafile->debug($libraries);
			
			/*
			*	Retrieve one library infos
			*/
			echo '<h1>One library infos</h1>';
			$library_info = $seafile->getLibraryInfo($libraries[0]->id);
			$seafile->debug($library_info);
			
			/*
			*	Retrieve all libraries infos
			*/
			echo '<h1>All libraries infos</h1>';
			$libraries_info = $seafile->getLibrariesInfo($libraries);
			$seafile->debug($libraries_info);
			
			/*
			*	List one library entries
			*/
			echo '<h1>One library entries</h1>';
			$files = $seafile->listDirectoryEntries($libraries[0]->id);
			$seafile->debug($files);
			
			/*
			*	List all libraries entries
			*/
			echo '<h1>All libraries entries</h1>';
			$all_file = $seafile->listDirectoriesEntries($libraries);
			$seafile->debug($all_file);
			
			/*
			*	Retrieve one file download link from first library
			*/
			echo '<h1>One file link</h1>';
			$file_link = $seafile->downloadFile($libraries[0]->id, $files[1]->name);
			$seafile->debug($file_link);
			
			/*
			*	Retrieve all files download link from all libraries
			*/
			echo '<h1>All files links</h1>';
			$files_link = array();
			$i = 0;
			foreach($libraries as $lib){
				$files_link[] = $seafile->downloadFiles($lib->id, $all_file[$i]);
				$i++;
			}
			$seafile->debug($files_link);
			
			/*
			*	Rename a seafile folder in a library
			*/
			echo '<h1>Rename a directory in library</h1>';
			$rename_dir = $seafile->renameDirectory($libraries[0]->id, 'test', 'rename');
			$seafile->debug($rename_dir);
			
			/*
			*	Create a seafile folder in a library
			*/
			echo '<h1>Create a new directory in library</h1>';
			$create_dir = $seafile->createNewDirectory($libraries[0]->id, 'new folder');
			$seafile->debug($create_dir);
			
			
		}
		?> 
	</div> 
	<!-- /container -->
    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <script src="../../assets/js/ie10-viewport-bug-workaround.js"></script>
  </body>
</html>