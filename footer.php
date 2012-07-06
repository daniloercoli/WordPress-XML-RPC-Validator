	</div><!--  closing "content" -->
	<div class="intro">
    <p>
        This validator checks the validity of the XML-RPC Endpoint of WordPress Sites.
        <br>
        Source code available <a href="https://github.com/daniloercoli/WordPress-XML-RPC-Validator">here</a>.
    </p>
	</div>
	<?php if ( 'home' != $action ) : ?>
	<a href="" id="xmlrpc_validator_log_view_switcher" onclick="xml_rpc_validator.toggle_log( ); return false;">Show Log</a>
	<?php endif; ?>
	<div style="clear: both;"></div>
	<div id="xmlrpc_validator_log" style="display: none">
	<?php echo $xml_rpc_validator_utils->show_log_on_video( ); ?>
	</div>
	
	<footer>
		<?php wp_footer(); ?>
	</footer>
</body>
</html>