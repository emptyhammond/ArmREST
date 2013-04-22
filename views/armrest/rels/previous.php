<html>
	<head>
		<title><?php echo __('Link relation') . ' - ' . Url::site(Request::current()->url(),true) ?></title>	
	</head>
	<body>
		<h1>Link Relation: <code><?php echo Url::site(Request::current()->url(),true) ?></code></h1>
		<p>
			Use this link relation to:
			<ul>
				<li>Request a representation of the <strong>previous</strong> resource in a collection</li>
			</ul>
		</p>
		<p>
			You can use the link's URI to:
			<ul>
				<li>Request the resource</li>
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
				<li><code>GET</code> to request the resource</li>
			</ul>
		</p>
	</body>
</html>