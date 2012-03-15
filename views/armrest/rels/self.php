<html>
	<head>
		<title><?php echo __('Link relation') . ' - ' . Url::site(Request::current()->url(),true) ?></title>	
	</head>
	<body>
		<h1>Link Relation: <code><?php echo Url::site(Request::current()->url(),true) ?></code></h1>
		<p>
			Use this link relation to:
			<ul>
				<li>Request a <strong>representation</strong> of the resource</li>
			</ul>
		</p>
		<p>
			You can use the link's URI to:
			<ul>
				<li>Request the resource</li>
				<li>Update the resource</li>
				<li>Delete the resource</li>
			</ul>
		</p>
		<p>
			Use a representation of media type:
			<ul>
			<?php
			foreach(ArmREST::types() as $type)
			{
				echo "<li><code>$type</code></li>";
			}
			?>
			</ul>
			and HTTP method:
			<ul>
				<li><code>GET</code> to view the resource</li>
				<li><code>POST</code> to update one or many of the properties of the resource</li>
				<li><code>PUT</code> to update the all properties of the resource</li>
				<li><code>DELETE</code> to delete the resource</li>
			</ul>
		</p>
	</body>
</html>