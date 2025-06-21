<?php
/**
 * The template for displaying a single Job post.
 */

get_header(); // This includes your theme's header (logo, navigation, etc.)
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">

        <?php
        // Start the WordPress Loop
        while ( have_posts() ) :
            the_post();

            // Get our custom field data
            $destination = get_post_meta( get_the_ID(), '_job_destination', true );
            $salary = get_post_meta( get_the_ID(), '_job_salary', true );
            ?>

            <article id="post-<?php the_ID(); ?>" <?php post_class('job-single-entry'); ?>>
                <header class="entry-header">
                    <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
                </header>

                <div class="job-details-meta">
                    <?php if ( ! empty( $destination ) ) : ?>
                        <div class="job-meta-item">
                            <strong>Destination:</strong> <?php echo esc_html( $destination ); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $salary ) ) : ?>
                         <div class="job-meta-item">
                            <strong>Salary:</strong> <?php echo esc_html( $salary ); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="entry-content">
                    <?php
                    // This displays the main content you write in the WordPress editor
                    the_content();
                    ?>
                </div>

            </article>

        <?php endwhile; // End of the loop. ?>

    </main>
</div>

<?php
get_footer(); // This includes your theme's footer.