<?php?><article id="post-<?php the_ID(); ?>" <?php post_class('article'); ?>>    	<header class="article-header">                                    <?php                 the_title( sprintf( '<h2 class="entry-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' );             ?>                        	</header>	<div class="article-content">        <?php            is_single() &&  the_content() || the_excerpt();            is_single() || printf("<a class='read-more' href='%s' >%s <span class='sr-only' >from %s</span>&rarr;</a>", esc_url(get_permalink()), __('Read More'), get_the_title());            wp_link_pages( array(                    'before'      => '<div class="page-links"><span class="page-links-title">' . __( 'Pages:' ) . '</span>',                    'after'       => '</div>',                    'link_before' => '<span>',                    'link_after'  => '</span>',                    'pagelink'    => '<span class="sr-only">' . __( 'Page' ) . ' </span>%',                    'separator'   => '<span class="sr-only">, </span>',            ) );        ?>	</div>	<footer class="article-footer">                        	</footer>        </article>