<?php
get_header();

?>
<div class="ffd-twbs">
<?php get_template_part('template-parts/slider'); ?>

<?php get_template_part('template-parts/the-loop'); ?>

<?php get_template_part('template-parts/ui-content'); ?>

</div>
<script type="text/javascript">
    jQuery(document).ready(function(){
        app.common.initScripts();
    });
</script>

<?php get_footer(); ?>