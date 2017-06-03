<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equip="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>Welcome to <?=$emailVars['org']?></title>
</head>

<body style="margin:0;font-family:Arial;font-size:14px;line-height:1.3em"><table style="width:100%"><tr><td>
	<p>Congratulations! You've been added to the <?=$emailVars['org']?> event.</p>
	<p>To log in, head to <a href="<?=$emailVars['activationLink']?>"><?=$emailVars['activationLink']?></a> to setup your account!</p>
</td></tr></table></body>
</html>
