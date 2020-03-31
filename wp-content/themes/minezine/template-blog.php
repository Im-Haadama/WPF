<?php
/**
 * Template Name: Blog
 * The template file for pages without right sidebar.
 * @package MineZine
 * @since MineZine 1.0.0
*/
get_header(); ?>
<table>
    <tr><td>
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    <div class="content-headline">
      <h1 class="entry-headline"><span class="entry-headline-text"><?php the_title(); ?></span></h1>
<?php minezine_get_breadcrumb(); ?>
    </div>
<?php minezine_get_display_image_page(); ?>
    <div class="entry-content">
<?php the_content(); ?>
<?php wp_link_pages( array( 'before' => '<p class="page-link"><span>' . __( 'Pages:', 'minezine' ) . '</span>', 'after' => '</p>' ) ); ?>
<?php edit_post_link( __( 'Edit', 'minezine' ), '<p>', '</p>' ); ?>
<?php endwhile; endif; ?>
        </td>
    <td>
    <aside id="secondary" class="widget-area" role="complementary">
		<?php dynamic_sidebar( 'sidebar-1' ); ?>
    </aside><!-- #secondary -->
    </td>
    </tr>
</table>
<?php comments_template( '', true ); ?>
    </div>   
  </div> <!-- end of content -->
<?php get_footer(); ?>