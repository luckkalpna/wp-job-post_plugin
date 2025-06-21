<?php
/**
 * Plugin Name:       Simple Job Listings
 * Description:       A dynamic job listings plugin with a custom accordion frontend.
 * Version:           3.1 (Structured content with UL lists)
 * Author:            Kalpana
 */

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Flush rewrite rules on plugin activation.
 */
function sjl_plugin_activation() {
    sjl_create_job_post_type();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'sjl_plugin_activation' );

/**
 * Flush rewrite rules on plugin deactivation.
 */
function sjl_plugin_deactivation() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'sjl_plugin_deactivation' );

// =============================================================================
// 1. CREATE CUSTOM POST TYPE ('job')
// =============================================================================
function sjl_create_job_post_type() {
    $labels = array(
        'name'               => _x( 'Jobs', 'Post type general name', 'sjl' ),
        'singular_name'      => _x( 'Job', 'Post type singular name', 'sjl' ),
        'menu_name'          => _x( 'Jobs', 'Admin Menu text', 'sjl' ),
        'add_new_item'       => __( 'Add New Job', 'sjl' ),
        'edit_item'          => __( 'Edit Job', 'sjl' ),
        'new_item'           => __( 'New Job', 'sjl' ),
        'view_item'          => __( 'View Job', 'sjl' ),
        'all_items'          => __( 'All Jobs', 'sjl' ),
        'search_items'       => __( 'Search Jobs', 'sjl' ),
        'not_found'          => __( 'No jobs found.', 'sjl' ),
    );
    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'job' ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 5,
        'menu_icon'          => 'dashicons-businessman',
        'supports'           => array( 'title' ), // MODIFIED: 'editor' is removed
    );
    register_post_type( 'job', $args );
}
add_action( 'init', 'sjl_create_job_post_type' );


// =============================================================================
// 2. ADD CUSTOM FIELDS (META BOXES) - UPDATED FOR STRUCTURED DESCRIPTION
// =============================================================================
function sjl_add_meta_boxes() {
    add_meta_box('sjl_job_details_meta_box', 'Job Details', 'sjl_job_details_meta_box_html', 'job', 'normal', 'high');
}
add_action( 'add_meta_boxes', 'sjl_add_meta_boxes' );

function sjl_job_details_meta_box_html( $post ) {
    // Get existing values
    $company_name     = get_post_meta( $post->ID, '_job_company_name', true );
    $work_mode        = get_post_meta( $post->ID, '_job_work_mode', true);
    $responsibilities = get_post_meta( $post->ID, '_job_responsibilities', true );
    $requirements     = get_post_meta( $post->ID, '_job_requirements', true );
    $benefits         = get_post_meta( $post->ID, '_job_benefits', true );

    wp_nonce_field( 'sjl_save_job_details', 'sjl_nonce' );
    ?>
    <p>
        <label for="job_company_name"><strong>Company Name:</strong></label><br>
        <input type="text" id="job_company_name" name="job_company_name" value="<?php echo esc_attr( $company_name ); ?>" class="widefat" placeholder="e.g., Paradisosolutions">
    </p>
    <p>
        <label for="work_mode"><strong>Working Mode:</strong></label><br>
        <input type="text" id="work_mode" name="work_mode" value="<?php echo esc_attr( $work_mode ); ?>" class="widefat" placeholder="e.g., Remote, In-Office">
    </p>

    <hr>
    <p><strong>Instructions:</strong> For the sections below, enter one item per line. Each line will become a list item.</p>
    <div class="sjl-editor-section">
        <label for="job_responsibilities"><strong>Responsibilities:</strong></label>
        <?php wp_editor( $responsibilities, 'job_responsibilities', array('textarea_name' => 'job_responsibilities', 'media_buttons' => false, 'textarea_rows' => 10) ); ?>
    </div>
    <div class="sjl-editor-section" style="margin-top: 20px;">
        <label for="job_requirements"><strong>Requirements:</strong></label>
        <?php wp_editor( $requirements, 'job_requirements', array('textarea_name' => 'job_requirements', 'media_buttons' => false, 'textarea_rows' => 10) ); ?>
    </div>
    <div class="sjl-editor-section" style="margin-top: 20px;">
        <label for="job_benefits"><strong>Role Benefits:</strong></label>
        <?php wp_editor( $benefits, 'job_benefits', array('textarea_name' => 'job_benefits', 'media_buttons' => false, 'textarea_rows' => 10) ); ?>
    </div>
    <?php
}

function sjl_save_job_details( $post_id ) {
    // Standard security checks
    if ( ! isset( $_POST['sjl_nonce'] ) || ! wp_verify_nonce( $_POST['sjl_nonce'], 'sjl_save_job_details' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;
    if ( 'job' !== get_post_type( $post_id ) ) return;

    // Save standard text fields
    if ( isset( $_POST['job_company_name'] ) ) {
        update_post_meta( $post_id, '_job_company_name', sanitize_text_field( $_POST['job_company_name'] ) );
    }
    if ( isset( $_POST['work_mode'] ) ) {
        update_post_meta( $post_id, '_job_work_mode', sanitize_text_field( $_POST['work_mode'] ) );
    }

    // Save content from the wp_editor fields.
    // wp_kses_post is still good here as it will clean the text before we process it later.
    if ( isset( $_POST['job_responsibilities'] ) ) {
        update_post_meta( $post_id, '_job_responsibilities', wp_kses_post( $_POST['job_responsibilities'] ) );
    }
    if ( isset( $_POST['job_requirements'] ) ) {
        update_post_meta( $post_id, '_job_requirements', wp_kses_post( $_POST['job_requirements'] ) );
    }
    if ( isset( $_POST['job_benefits'] ) ) {
        update_post_meta( $post_id, '_job_benefits', wp_kses_post( $_POST['job_benefits'] ) );
    }
    
    // Clear main post content to avoid confusion
    remove_action( 'save_post', 'sjl_save_job_details' );
    wp_update_post( array( 'ID' => $post_id, 'post_content' => '' ) );
    add_action( 'save_post', 'sjl_save_job_details' );
}
add_action( 'save_post', 'sjl_save_job_details' );


// =============================================================================
// NEW: ADVANCED HELPER FUNCTION TO PARSE MIXED CONTENT
// =============================================================================
/**
 * Parses text with paragraphs and list items (prefixed with *, -, or •)
 * into clean HTML.
 *
 * @param string $text The raw text from the wp_editor field.
 * @return string The formatted HTML.
 */
function sjl_parse_and_format_content( $text ) {
    if ( empty( trim( $text ) ) ) {
        return '';
    }

    // Run the standard WordPress content filters first. This handles wpautop, shortcodes, etc.
    // This is more robust than trying to parse it all ourselves.
    $content = apply_filters( 'the_content', $text );
    
    // Now, we just need to ensure our list styling is correct if the user
    // simply used the visual editor, which is the best case.
    // If you need more complex custom parsing, it would go here, but
    // letting WordPress do the work is always better.

    // For your specific case where you might want to force a UL/LI structure
    // from lines starting with '*', we can do this:
    
    $lines = explode( "\n", trim( $text ) );
    $html = '';
    $in_list = false;

    foreach ( $lines as $line ) {
        $line = trim( $line );

        // Check if the line is a list item (starts with *, -, or the actual bullet char •)
        if ( strpos( $line, '*' ) === 0 || strpos( $line, '-' ) === 0 || strpos( $line, '•' ) === 0 ) {
            if ( ! $in_list ) {
                $html .= '<ul>';
                $in_list = true;
            }
            // Remove the marker and trim whitespace
            $list_item_content = ltrim( $line, '*-> ' );
            $html .= '<li>' . wp_kses_post( $list_item_content ) . '</li>';
        } else {
            // This is not a list item
            if ( $in_list ) {
                $html .= '</ul>'; // Close the list
                $in_list = false;
            }
            if ( ! empty( $line ) ) {
                // Wrap non-empty, non-list lines in <p> tags
                $html .= '<p>' . wp_kses_post( $line ) . '</p>';
            }
        }
    }

    // Close any open list at the end of the text
    if ( $in_list ) {
        $html .= '</ul>';
    }

    return $html;
}


// =============================================================================
// 3. SHORTCODE TO DISPLAY JOBS - MODIFIED TO USE HELPER FUNCTION
// =============================================================================
function sjl_display_dynamic_job_listings( $atts ) {
    ob_start();
    $job_query = new WP_Query( array('post_type' => 'job', 'posts_per_page' => -1, 'post_status' => 'publish') );

    echo '<div class="sjl-container">';
    if ( $job_query->have_posts() ) {
        while ( $job_query->have_posts() ) {
            $job_query->the_post();
            $post_id          = get_the_ID();
            $company_name     = get_post_meta( $post_id, '_job_company_name', true );
            $work_mode        = get_post_meta( $post_id, '_job_work_mode', true );
            
            $responsibilities = get_post_meta( $post_id, '_job_responsibilities', true );
            $requirements     = get_post_meta( $post_id, '_job_requirements', true );
            $benefits         = get_post_meta( $post_id, '_job_benefits', true );
            ?>
            <div class="accordion-item">
                <div class="accordion-header" onclick="toggleAccordion(this)">
                    <div class="header-content">
                        <div class="company-name"><?php echo esc_html( $company_name ); ?></div>
                        <div class="job-title"><?php the_title(); ?></div>
                        <div class="job-meta-info">
                            <?php if ( ! empty( $work_mode ) ) : ?>
                                <span class="work-mode"><?php echo esc_html( $work_mode ); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="header-actions">
                        <button class="action-btn bookmark-btn" data-post-id="<?php echo esc_attr( $post_id ); ?>" onclick="handleBookmark(event)">Bookmark</button>
                        <button class="apply-btn" onclick="openPopup(event, '<?php echo esc_attr( get_the_title() ); ?>')">Apply Now</button>
                        <div class="expand-icon"></div>
                    </div>
                </div>
                <div class="accordion-content">
                    <div class="job-description">
    
    <?php if ( ! empty( $responsibilities ) ) : ?>
    <div class="job-section job-responsibilities">
        <h4>Responsibilities</h4>
        <?php // MODIFIED: Use our new advanced parser function ?>
        <?php echo sjl_parse_and_format_content( $responsibilities ); ?>
    </div>
    <?php endif; ?>
    
    <?php if ( ! empty( $requirements ) ) : ?>
    <div class="job-section job-requirements">
        <h4>Requirements</h4>
        <?php // MODIFIED: Use our new advanced parser function ?>
        <?php echo sjl_parse_and_format_content( $requirements ); ?>
    </div>
    <?php endif; ?>

    <?php if ( ! empty( $benefits ) ) : ?>
    <div class="job-section job-benefits">
        <h4>Role Benefits</h4>
        <?php // MODIFIED: Use our new advanced parser function ?>
        <?php echo sjl_parse_and_format_content( $benefits ); ?>
    </div>
    <?php endif; ?>

</div>
                </div>
            </div>
            <?php
        }
    } else {
        echo '<p>No job openings at the moment. Please check back later.</p>';
    }
    wp_reset_postdata();

    echo '</div>'; // End .sjl-container
    return ob_get_clean();
}
add_shortcode( 'job_listings', 'sjl_display_dynamic_job_listings' );


// =============================================================================
// 4. ADD THE POPUP FORM TO THE FOOTER
// =============================================================================
function sjl_add_popup_form_to_footer() {
    global $post;
    if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'job_listings' ) ) {
    ?>
    <div id="sjlPopupForm" class="popup-overlay">
        <div class="popup-content">
             <span class="close-btn" onclick="closePopup()">×</span>
             <h2>Apply for Job</h2>
             <p>Please fill out the form below to apply.</p>
             
             <?php
             echo do_shortcode('[elementor-template id="149228"]');
             ?>

        </div>
    </div>
    <?php
    }
}
add_action('wp_footer', 'sjl_add_popup_form_to_footer');


// =============================================================================
// 5. ENQUEUE YOUR STYLES - UPDATED
// =============================================================================
function sjl_enqueue_custom_styles() {
    // CSS FIXED: Corrected syntax error where job-section styles were inside another rule.
    $css = '
    .sjl-container * { margin: 0; padding: 0; box-sizing: border-box; }
    .sjl-container { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; max-width: 1200px; margin: 20px auto; }
    .accordion-item { background: white; border: 1px solid #e0e0e0; border-radius: 8px; margin-bottom: 16px; transition: all 0.3s ease; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); }
    .accordion-item:hover { box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15); }
    .accordion-header { display: flex; align-items: center; justify-content: space-between; padding: 20px 24px; cursor: pointer; transition: all 0.3s ease; }
    .accordion-header:hover { background-color: #fafafa; }
    .header-content { flex: 1; }
    .company-name { font-size: 0.9rem; color: #666; margin-bottom: 8px; font-weight: 500; }
    .job-title { font-size: 1.4rem; font-weight: 600; color: #000; margin-bottom: 4px; line-height: 1.3; }
    .job-meta-info { display: flex; align-items: center; flex-wrap: wrap; font-size: 0.95rem; color: #666; }
    .header-actions { display: flex; align-items: center; gap: 16px; }
    .action-btn { background: none; border: none; cursor: pointer; padding: 8px; border-radius: 4px; transition: background-color 0.3s ease; }
    .action-btn:hover { background-color: #f0f0f0; }
    .bookmark-btn { font-size: 0.9rem; font-weight: 500; border: 1px solid #ccc; padding: 5px 10px; border-radius: 6px; }
    .bookmark-btn.saved { background-color: #f0e68c; border-color: #e0d57b; font-weight: 600; }
    .apply-btn { background: #4caf50; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-size: 0.9rem; font-weight: 500; cursor: pointer; transition: background-color 0.3s ease; }
    .apply-btn:hover { background: #45a049; }
    .expand-icon { width: 32px; height: 32px; border: 1px solid #ddd; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; background: white; cursor: pointer; margin-left: 16px; position: relative; }
    .expand-icon.active { background: #333; border-color: #333; transform: rotate(45deg); }
    .expand-icon::before, .expand-icon::after { content: ""; position: absolute; background: #333; transition: all 0.3s ease; }
    .expand-icon::before { width: 12px; height: 1.5px; }
    .expand-icon::after { width: 1.5px; height: 12px; }
    .expand-icon.active::before, .expand-icon.active::after { background: white; }
    .accordion-content { max-height: 0; overflow: hidden; transition: max-height 0.4s ease-out, padding 0.3s ease; background: #fafafa; }
    .accordion-content.active { max-height: 2000px; 
    // padding: 24px; 
    border-top: 1px solid #e0e0e0; }
    .job-description ul { list-style-position: inside; padding-left: 10px; }
    .job-description p, .job-description ul, .job-description ol { margin-bottom: 1em; }
    .popup-overlay { display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.6); justify-content: center; align-items: center; z-index: 9999; }
    .popup-content { background: white; padding: 30px; border-radius: 12px; width: 90%; max-width: 600px; position: relative; color: #333; display: grid; justify-items: center }
    .close-btn { position: absolute; top: 10px; right: 15px; font-size: 24px; cursor: pointer; color: #aaa; }
    .close-btn:hover { color: #333; }
    .popup-content h2 { margin-bottom: 10px; }
    .popup-content p { margin-bottom: 20px; }
    .popup-content input, .popup-content textarea { width: 100%; padding: 10px; margin-top: 5px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 6px; }
    .popup-content input[readonly] { background-color: #f4f4f4; cursor: not-allowed; }
    .popup-content button[type="submit"] { background-color: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; margin-top: 10px; }

    /* Styles for the structured content sections */
    .job-section { padding: 16px; margin-bottom: 20px; border-left: 4px solid #e0e0e0; background-color: #fdfdfd; }
    .job-section h4 { font-size: 1.1rem; font-weight: 600; color: #333; margin-top: 0; margin-bottom: 12px; }
    .job-responsibilities { border-left-color: #4CAF50; }
    .job-requirements { border-left-color: #f44336; }
    .job-benefits { border-left-color: #2196F3; 
    strong { font-weight: 700 !important; }
    }
    ';
    wp_register_style( 'sjl-custom-styles', false );
    wp_enqueue_style( 'sjl-custom-styles' );
    wp_add_inline_style( 'sjl-custom-styles', $css );
}
add_action( 'wp_enqueue_scripts', 'sjl_enqueue_custom_styles' );


// =============================================================================
// 6. ENQUEUE YOUR JAVASCRIPT - UNCHANGED
// =============================================================================
function sjl_enqueue_custom_scripts() {
    global $post;
    if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'job_listings' ) ) {
    ?>
    <script type="text/javascript" id="sjl-custom-scripts">
    document.addEventListener('DOMContentLoaded', function() {
        const savedBookmarks = JSON.parse(localStorage.getItem('sjl_bookmarks')) || [];
        savedBookmarks.forEach(function(bookmarkedId) {
            const btn = document.querySelector(`.bookmark-btn[data-post-id="${bookmarkedId}"]`);
            if (btn) {
                btn.classList.add('saved');
                btn.textContent = 'Bookmarked';
            }
        });
    });

    function toggleAccordion(header) {
        const item = header.parentElement;
        const content = item.querySelector(".accordion-content");
        const icon = header.querySelector(".expand-icon");
        const isActive = content.classList.contains("active");

        document.querySelectorAll(".accordion-item").forEach((otherItem) => {
            if (otherItem !== item) {
                otherItem.querySelector(".accordion-content").classList.remove("active");
                otherItem.querySelector(".expand-icon").classList.remove("active");
            }
        });

        if (isActive) {
            content.classList.remove("active");
            icon.classList.remove("active");
        } else {
            content.classList.add("active");
            icon.classList.add("active");
        }
    }

    function handleBookmark(event) {
        event.stopPropagation();
        const btn = event.currentTarget;
        const postId = btn.getAttribute('data-post-id');
        if (!postId) return;

        let savedBookmarks = JSON.parse(localStorage.getItem('sjl_bookmarks')) || [];
        
        if (btn.classList.contains('saved')) {
            savedBookmarks = savedBookmarks.filter(id => id !== postId);
            btn.classList.remove('saved');
            btn.textContent = 'Bookmark';
        } else {
            savedBookmarks.push(postId);
            btn.classList.add('saved');
            btn.textContent = 'Bookmarked';
        }
        localStorage.setItem('sjl_bookmarks', JSON.stringify(savedBookmarks));
    }
    
    function openPopup(event, jobTitle = '') {
        event.stopPropagation();
        const popup = document.getElementById("sjlPopupForm");
        if (popup) {
            popup.style.display = "flex";
            const positionInput = document.getElementById('applicant_position');
            if (positionInput) {
                positionInput.value = jobTitle;
            }
        }
    }

    function closePopup() {
        const popup = document.getElementById("sjlPopupForm");
        if (popup) {
            popup.style.display = "none";
        }
    }

    // This function is no longer used since we removed the "show more" button
    // but we can leave it in case you want to use it again later.
    function toggleShowMore(button) {
        const hiddenContent = button.closest('.job-description').querySelector('.hidden-content');
        if (!hiddenContent) return;

        const isExpanded = hiddenContent.classList.contains('visible');
        if (isExpanded) {
            hiddenContent.classList.remove('visible');
            button.classList.remove('expanded');
            button.textContent = 'Show more';
        } else {
            hiddenContent.classList.add('visible');
            button.classList.add('expanded');
            button.textContent = 'Show less';
        }
    }
    </script>
    <?php
    }
}
add_action( 'wp_footer', 'sjl_enqueue_custom_scripts' );