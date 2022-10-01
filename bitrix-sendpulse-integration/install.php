<?php
header('Content-Type: text/html; charset=UTF-8');
$protocol = $_SERVER['SERVER_PORT'] == '443' ? 'https' : 'http';
$host = explode(':', $_SERVER['HTTP_HOST']);
$host = $host[0];
define('BP_APP_HANDLER', $protocol.'://'.$host.explode('?', $_SERVER['REQUEST_URI'])[0]);
$urlForRobot = $protocol.'://'.$host.explode('?', '/apps/robots/bitrix-sendpulse-integration/handler.php')[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title></title>
</head>
<body>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
<script src="//api.bitrix24.com/api/v1/"></script>
<?if (!isset($_POST['PLACEMENT']) || $_POST['PLACEMENT'] === 'DEFAULT'):?>
<h1>Robots Manager</h1>
<div class="container-fluid">
<div class="container-fluid">
 		<button onclick="installRobot();" class="btn btn-primary">Install</button>
 		<button onclick="uninstallRobot();" class="btn btn-danger">Delete</button>
 	</div>
 	<hr/>
 	<div class="container-fluid">
 		<button onclick="getList();" class="btn btn-light">Get list of installed robots</button>
 	</div>
 </div>
 <script type="text/javascript">
 	document.body.style.display = 'none';
 	BX24.init(function()
 	{
 		document.body.style.display = '';
 	});
 
 	function installRobot()
 	{
		//install params, can be changed according to user requirements
 		var params = {
 			'CODE': 'bitrix-sendpulse-integration',
			'HANDLER': <?=json_encode($urlForRobot)?>,
 			'AUTH_USER_ID': 1,
 			'NAME': 'Selleris',
 			'USE_PLACEMENT': 'N',
 			'PLACEMENT_HANDLER': '<?=BP_APP_HANDLER?>',
			 'PROPERTIES': {
				'city': {
					'Name': 'city',
					'Type': 'string',
				},
				'delivery_address': {
					'Name': 'delivery_address',
					'Type': 'string',
				},
				'payment_method_name': {
					'Name': 'payment_method_name',
					'Type': 'string',
				},
				'payment_method_id': {
					'Name': 'payment_method_id',
					'Type': 'string',
				},
				'order_date': {
					'Name': 'order_date',
					'Type': 'string',
				},
				'payment_status': {
					'Name': 'payment_status',
					'Type': 'string',
				},
				'ttn_number': {
					'Name': 'ttn_number',
					'Type': 'string',
				},
				'ttn_date': {
					'Name': 'ttn_date',
					'Type': 'string',
				},
			}
 		};
 
 		BX24.callMethod(
 			'bizproc.robot.add',
 			params,
 			function(result)
 			{
 				if(result.error())
 					alert("Error: " + result.error());
 				else
 					alert("Successfully");
 			}
 		);
 	}
 
 	function uninstallRobot()
 	{
 		BX24.callMethod(
 				'bizproc.robot.delete',
 				{
 					'CODE': 'bitrix-sendpulse-integration'
 				},
 				function(result)
 				{
 					if(result.error())
 						alert('Error: ' + result.error());
 					else
 						alert("Successfully");
 				}
 		);
 	}
 
 	function getList()
 	{
 
 		BX24.callMethod(
 			'bizproc.robot.list',
 			{},
 			function(result)
 			{
 				if(result.error())
 					alert("Error: " + result.error());
 				else
 					alert("Codes of installed robots: " + result.data().join(', '));
 			}
 		);
 	}
 
 </script>
 <?php else:?>
 	<form name="props" class="container-fluid">
 <?php
 $options = json_decode($_POST['PLACEMENT_OPTIONS'], true);

 foreach ($options['properties'] as $id => $property)
 {
 	$multiple = isset($property['MULTIPLE']) && $property['MULTIPLE'] === 'Y';
 	$val = (array) $options['current_values'][$id];
 	if (!$val)
 	{
 		$val[] = '';
 	}
 
 	if ($multiple)
 	{
 		$val[] = '';
 	}
 
 	$name = $multiple ? $id.'[]' : $id;
 	?>
 	<div class="form-group">
 		<label><?=htmlspecialchars($property['NAME'])?>:</label>
 		<?foreach ($val as $v):?>
 		<p><input name="<?=$name?>" value="<?=htmlspecialchars((string)$v)?>" class="form-control" onchange="setPropertyValue('<?=$id?>', this.name, <?=(int)$multiple?>)"></p>
 		<?endforeach;?>
 	</div>
 	<?
 }
 ?>
 		<script>
 			function setPropertyValue(name, inputName, multiple)
 			{
 				var form = new FormData(document.forms.props);
 				var value = multiple? form.getAll(inputName) : form.get(inputName);
 				var params = {};
 				params[name] = value;
 
 				BX24.placement.call(
 					'setPropertyValue',
 					params
 				)
 			}
 		</script>
 	</form>
 <?php endif;?>
 </body>
</html>