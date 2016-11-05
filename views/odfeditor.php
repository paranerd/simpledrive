<!DOCTYPE HTML>
<html style="width:100%; height:100%; margin:0px; padding:0px" xml:lang="en" lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <link rel="stylesheet" href="assets/css/layout.css" />
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <title>Local Editor</title>
  </head>

  <body style="width:100%; height:100%; margin:0px; padding:0px">
    <div id="editorContainer" style="z-index: 0; width:100%; height:100%; margin:0px; padding:0px">

		<!-- Notification -->
		<div id="notification" class="popup hidden">
			<div id="note-icon" class="icon-info"></div>
			<div id="note-title"></div>
			<div id="note-msg"></div>
			<span class="close" onclick="Util.hideNotification();"> &times;</span>
		</div>
    </div>

	<script type="text/javascript" src="lib/jquery/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="assets/js/util.js"></script>
	<script type="text/javascript" src="plugins/webodf/wodotexteditor.js" type="text/javascript" charset="utf-8"></script>
	<script type="text/javascript" src="plugins/webodf/FileSaver.js" type="text/javascript" charset="utf-8"></script>
	<script type="text/javascript" src="plugins/webodf/localfileeditor.js" type="text/javascript" charset="utf-8"></script>

	<script>
		var user	= '<?php echo $user; ?>';
		var file	= '<?php echo $_POST['elem']; ?>';
		var token	= "<?php echo $token; ?>";
	</script>
  </body>
</html>
