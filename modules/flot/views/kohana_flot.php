<?php defined('SYSPATH') or die('No direct access allowed.'); ?>
<<?php echo $type.html::attributes($attr) ?>></<?php echo $type ?>>
<script type="text/javascript">
$.plot($('<?php echo $type ?>#<?php echo $attr['id'] ?>'),
[
<?php echo "\t".implode(",\n\t", $dataset)."\n" ?>
],
<?php echo $options."\n" ?>
);
</script>