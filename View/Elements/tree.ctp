<?php
/**
 * TreeElement
 * Renders a jqTree tree
 *
 * @param string $modelName
 * @todo pass jqTree data url from controller
 */

$modelName = (isset($modelName)) ? $modelName : null;
if (!$modelName) {
	debug("Can not display tree, because the 'modelName' is missing");
	return;
}
$modelName = pluginSplit($modelName, true);
$modelName = Inflector::underscore($modelName[0]) . Inflector::underscore($modelName[1]);
?>
<?php $this->Html->script('/tree_mod/js/tree.jquery', array('inline' => false)); ?>
<?php $this->Html->css('/tree_mod/css/jqtree', null, array('inline' => false)); ?>
<div class="element" style="margin-bottom: 2em;">

	<?php $treeId = md5(uniqid('jqtree')); ?>
	<div id="<?php echo $treeId; ?>"></div>
	<script>
	var data_<?php echo $treeId; ?> = <?php echo json_encode($jqTree); ?>;

	$(function() {
		var selector = '#<?php echo $treeId; ?>';
		var dataUrl = '<?php echo Router::url(array('plugin' => 'tree_mod', 'controller' => 'jq_tree', 'action' => 'alter', $modelName)) ?>';
		console.log(dataUrl);
	    $(selector).tree({
	        data: data_<?php echo $treeId; ?>,
	        dragAndDrop: true,
	        autoOpen: 0
	    })
	    .bind(
    	    'tree.move',
    	    function(event) {
				/*
				console.log('moved_node', event.move_info.moved_node);
				console.log('target_node', event.move_info.target_node);
				console.log('position', event.move_info.position);
				console.log('previous_parent', event.move_info.previous_parent);
				 console.log(event);
				*/

				var url = dataUrl;
				var data = {
					node: event.move_info.moved_node.id,
					target: event.move_info.target_node.id,
					position: event.move_info.position,
					prev: event.move_info.previous_parent.id
				};

				console.log(url);
				$.ajax({
					type: 'POST',
					async: false,
					dataType: "json",
					url: url,
					cache: false,
					data: data,
					success: function(json) {
						console.log(json);

						if (json.success !== true) {
							alert('Failed to alter position');
			        	    event.preventDefault();
						}
	        	    },
	        	    error: function() {
						alert('An error occured');
		        	    event.preventDefault();
	        	    }
				});
    	    }
    	);
	});
	</script>

	<?php //debug($jqTree); ?>
	<?php //debug($tree); ?>
</div>