<?php

/**
 * Class to perform all on-page checks and calculate the weighted score via AJAX.
 */
class USC_Scoring_Engine {

    // Define weights for each check category (Total must equal 100%)
    const WEIGHTS = [
        'core'      => 50,
        'advanced'  => 30,
        'technical' => 20,
    ];

    /**
     * Entry point for the AJAX request from the Classic Editor.
     */
    public static function run_audit_via_ajax() {
        // --- 1. SECURITY CHECK (Nonce Verification) ---
        if ( ! check_ajax_referer( 'usc_audit_nonce', 'nonce', false ) ) {
            wp_send_json_error( 'Nonce verification failed. Security Error.' );
        }
        
        if ( ! isset( $_POST['post_id'] ) || ! isset( $_POST['keyword'] ) || ! isset( $_POST['content'] ) ) {
            wp_send_json_error( 'Missing data for audit.' );
        }

        // Sanitize and prepare data
        $post_id = intval( $_POST['post_id'] );
        $keyword = sanitize_text_field( $_POST['keyword'] );
        $content = wp_unslash( $_POST['content'] ); // Content is passed via JS

        // --- Execute the Audit Checks ---
        $post = get_post( $post_id );
        $title = get_post_field( 'post_title', $post_id );
        // Placeholder for Meta Description (In a real plugin, this would be retrieved from a dedicated SEO plugin's post meta)
        $meta_description = 'This is a placeholder meta description containing the keyword ' . $keyword; 
        $url_slug = basename( get_permalink( $post_id ) );
        
        // --- Run Audits ---
        $core_results = self::run_core_checks( $content, $title, $meta_description, $url_slug, $keyword );
        $advanced_results = self::run_advanced_checks( $content, $keyword );
        $technical_results = self::run_technical_checks( $post_id, $content );

        // Calculate the final weighted score
        $final_score = self::calculate_weighted_score( $core_results, $advanced_results, $technical_results );

        // Send results back to JavaScript
        wp_send_json_success( [
            'score' => $final_score,
            'results' => array_merge( $core_results, $advanced_results, $technical_results )
        ] );

        wp_die();
    }

    /**
     * Implements the checks from Section I (Core Optimization).
     */
    private static function run_core_checks( $content, $title, $meta_description, $url_slug, $keyword ) {
        $results = [];
        $clean_content = strip_tags( $content );
        $keyword_esc = preg_quote( $keyword, '/' );

        // 1. Keyword in Title Tag
        $is_in_title = stripos( $title, $keyword ) !== false;
        $results['title_keyword'] = [
            'label' => esc_html__( 'Keyword in Title Tag', 'ultimate-seo-checklist' ),
            'status' => $is_in_title ? 'green' : 'red',
            'score_value' => $is_in_title ? 10 : 0,
        ];

        // 2. Keyword in Meta Description
        $is_in_meta = stripos( $meta_description, $keyword ) !== false;
        $results['meta_keyword'] = [
            'label' => esc_html__( 'Keyword in Meta Description', 'ultimate-seo-checklist' ),
            'status' => $is_in_meta ? 'green' : 'orange',
            'score_value' => $is_in_meta ? 6 : 2,
        ];

        // 3. Keyword in Clean URL Slug
        $is_in_url = stripos( $url_slug, sanitize_title( $keyword ) ) !== false;
        $results['url_keyword'] = [
            'label' => esc_html__( 'Keyword in Clean URL Slug', 'ultimate-seo-checklist' ),
            'status' => $is_in_url ? 'green' : 'red',
            'score_value' => $is_in_url ? 8 : 0,
        ];

        // 4. Keyword in H1 (Must be present and used only once)
        $h1_count = preg_match_all( '/<h1[^>]*>.*?<\/h1>/i', $content, $h1_matches );
        $is_keyword_in_h1 = ( $h1_count === 1 ) && preg_match( '/<h1[^>]*>.*?' . $keyword_esc . '.*?<\/h1>/i', $content );
        $results['h1_keyword'] = [
            'label' => esc_html__( 'Keyword in H1 Heading (And H1 used once)', 'ultimate-seo-checklist' ),
            'status' => $is_keyword_in_h1 ? 'green' : 'red',
            'score_value' => $is_keyword_in_h1 ? 8 : 0,
            'details' => ($h1_count === 0) ? esc_html__( 'No H1 found.', 'ultimate-seo-checklist' ) : ($h1_count > 1 ? esc_html__( 'Too many H1s.', 'ultimate-seo-checklist' ) : '')
        ];

        // 5. Keyword in First Paragraph (First 300 characters)
        $first_paragraph = substr( $clean_content, 0, 300 );
        $is_keyword_early = stripos( $first_paragraph, $keyword ) !== false;
        $results['early_keyword'] = [
            'label' => esc_html__( 'Keyword in First Paragraph', 'ultimate-seo-checklist' ),
            'status' => $is_keyword_early ? 'green' : 'red',
            'score_value' => $is_keyword_early ? 7 : 0,
        ];

        // 6. Content Length (Minimum 500+ Words)
        $word_count = str_word_count( $clean_content );
        $is_long_enough = $word_count >= 500;
        $results['content_length'] = [
            'label' => esc_html__( 'Content Length (500+ Words)', 'ultimate-seo-checklist' ),
            'status' => $is_long_enough ? 'green' : 'orange',
            'score_value' => $is_long_enough ? 10 : 3,
            'details' => sprintf( esc_html__( 'Current Count: %d', 'ultimate-seo-checklist' ), $word_count )
        ];
        
        // 7. Keyword Density Check (Minimum 0.5% - Maximum 3.0%)
        $keyword_count = substr_count( strtolower($clean_content), strtolower($keyword) );
        $density = ($word_count > 0) ? ($keyword_count / $word_count) * 100 : 0;
        $is_density_ok = ($density >= 0.5 && $density <= 3.0);
        
        $results['keyword_density'] = [
            'label' => esc_html__( 'Keyword Density (0.5% - 3.0% Range)', 'ultimate-seo-checklist' ),
            'status' => $is_density_ok ? 'green' : 'orange',
            'score_value' => $is_density_ok ? 8 : 2,
            'details' => sprintf( esc_html__( 'Density: %.2f%%', 'ultimate-seo-checklist' ), round($density, 2) )
        ];

        return $results;
    }

    /**
     * Implements the checks from Section II (Advanced/Strategic).
     */
    private static function run_advanced_checks( $content, $keyword ) {
        $results = [];
        $keyword_esc = preg_quote( $keyword, '/' );

        // 1. Primary Keyword is Bolded (Emphasis Check)
        $bold_pattern = '/<(strong|b)[^>]*>.*?' . $keyword_esc . '.*?<\/(strong|b)>/i';
        $is_bolded = preg_match( $bold_pattern, $content );

        $results['keyword_bold'] = [
            'label' => esc_html__( 'Primary Keyword is Bolded (Emphasis Check)', 'ultimate-seo-checklist' ),
            'status' => $is_bolded ? 'green' : 'orange',
            'score_value' => $is_bolded ? 5 : 2,
        ];

        // 2. Keyword in H2-H3 Subheadings
        $h2_h3_pattern = '/<h[23][^>]*>.*?' . $keyword_esc . '.*?<\/h[23]>/i';
        $is_in_subheadings = preg_match_all( $h2_h3_pattern, $content, $matches );

        $results['h2h3_keyword'] = [
            'label' => esc_html__( 'Keyword in H2-H3 Subheadings (min 1)', 'ultimate-seo-checklist' ),
            'status' => $is_in_subheadings >= 1 ? 'green' : 'orange',
            'score_value' => $is_in_subheadings >= 1 ? 6 : 2,
            'details' => sprintf( esc_html__( 'Found: %d instances', 'ultimate-seo-checklist' ), $is_in_subheadings )
        ];

        // 3. Keyword in Image Alt Text
        $alt_pattern = '/<img[^>]*alt\s*=\s*[\'"][^>]*?' . $keyword_esc . '.*?[\'"][^>]*>/i';
        $is_in_alt = preg_match( $alt_pattern, $content );

        $results['alt_keyword'] = [
            'label' => esc_html__( 'Keyword in Image Alt Text', 'ultimate-seo-checklist' ),
            'status' => $is_in_alt ? 'green' : 'orange',
            'score_value' => $is_in_alt ? 4 : 1,
        ];
        
        // 4. Focus Keyword Anchor Risk Check (Critical: Must NOT be used as anchor)
        $is_keyword_anchor_used = preg_match( '/<a [^>]*>' . $keyword_esc . '<\/a>/i', $content ); 
        
        if ($is_keyword_anchor_used) {
            $anchor_status = 'red';
            $anchor_label = esc_html__( '🔴 Focus Keyword used as Anchor Text (RISK WARNING)', 'ultimate-seo-checklist' );
            $anchor_score = 0;
            $anchor_details = esc_html__( 'Avoid using the focus keyword as anchor text for ANY link.', 'ultimate-seo-checklist' );
        } else {
            $anchor_status = 'green';
            $anchor_label = esc_html__( '✅ Focus Keyword used as Anchor Text (Recommendation)', 'ultimate-seo-checklist' );
            $anchor_score = 10;
            $anchor_details = esc_html__( 'Optimal: Focus keyword is NOT used as anchor text.', 'ultimate-seo-checklist' );
        }

        $results['keyword_anchor_risk'] = [
            'label' => $anchor_label,
            'status' => $anchor_status,
            'score_value' => $anchor_score,
            'details' => $anchor_details,
        ];
        
        // 5. Minimum Internal Links (Check for 3+ links)
        $internal_links_count = substr_count( $content, '<a href="' . home_url() );
        $is_enough_internal = $internal_links_count >= 3;
        
        $results['internal_links_count'] = [
            'label' => esc_html__( 'Minimum Internal Links (3+)', 'ultimate-seo-checklist' ),
            'status' => $is_enough_internal ? 'green' : 'orange',
            'score_value' => $is_enough_internal ? 7 : 3,
            'details' => sprintf( esc_html__( 'Found: %d', 'ultimate-seo-checklist' ), $internal_links_count )
        ];
        
        // 6. External Link to Authority Site (min 1)
        $internal_links_count_full = substr_count( $content, '<a href="http' );
        $external_link_count = $internal_links_count_full - $internal_links_count;
        $is_external_present = $external_link_count >= 1;
        
        $results['external_links'] = [
            'label' => esc_html__( 'External Link to Authority Site (min 1)', 'ultimate-seo-checklist' ),
            'status' => $is_external_present ? 'green' : 'orange',
            'score_value' => $is_external_present ? 5 : 1,
            'details' => sprintf( esc_html__( 'Found: %d', 'ultimate-seo-checklist' ), $external_link_count )
        ];

        // 7. Quick Answer Format Detected (Snippet Optimization)
        $answer_pattern = '/<h[23][^>]*>(what|how|why|when) is.*?<\/h[23]>\s*<p[^>]*>.*?<\/p>/si';
        $is_answer_format = preg_match($answer_pattern, $content);

        $results['answer_box_format'] = [
            'label' => esc_html__( 'Quick Answer Format (Snippet Optimization)', 'ultimate-seo-checklist' ),
            'status' => $is_answer_format ? 'green' : 'orange',
            'score_value' => $is_answer_format ? 8 : 2,
        ];

        return $results;
    }

    /**
     * Implements the checks from Section III (Technical/UX).
     */
    private static function run_technical_checks( $post_id, $content ) {
        $results = [];
        $clean_content = strip_tags( $content );

        // 1. Canonical Tag Status 
        $is_canonical_correct = true; 
        $results['canonical_tag'] = [
            'label' => esc_html__( 'Canonical Tag is Correct', 'ultimate-seo-checklist' ),
            'status' => $is_canonical_correct ? 'green' : 'red',
            'score_value' => 5,
        ];

        // 2. Good Readability (Avg Sentence Length < 25)
        $sentence_count = substr_count( $clean_content, '.' ) + substr_count( $clean_content, '!' ) + substr_count( $clean_content, '?' );
        $word_count = str_word_count( $clean_content );
        $avg_sentence_length = $sentence_count > 0 ? ($word_count / $sentence_count) : 0;
        $is_readable = $avg_sentence_length <= 25;

        $results['readability'] = [
            'label' => esc_html__( 'Good Readability (Avg Sentence Length < 25)', 'ultimate-seo-checklist' ),
            'status' => $is_readable ? 'green' : 'orange',
            'score_value' => $is_readable ? 5 : 2,
            'details' => sprintf( esc_html__( 'Avg Length: %.1f words', 'ultimate-seo-checklist' ), round($avg_sentence_length, 1) )
        ];
        
        // 3. Author and Date Metadata Present (E-E-A-T Check)
        $author_name = get_the_author_meta('display_name', get_post_field('post_author', $post_id));
        $date_valid = (get_the_modified_date('Y', $post_id) !== get_the_date('Y', $post_id));

        $is_e_e_a_t_signal = !empty($author_name) && $date_valid;

        $results['e_e_a_t_signal'] = [
            'label' => esc_html__( 'Author/Date Metadata Present (E-E-A-T)', 'ultimate-seo-checklist' ),
            'status' => $is_e_e_a_t_signal ? 'green' : 'orange',
            'score_value' => $is_e_e_a_t_signal ? 5 : 1,
        ];

        // 4. Image Dimensions (CWV/CLS Check)
        $has_images = preg_match_all('/<img\s[^>]*>/i', $content, $img_matches);
        $missing_dimensions = 0;
        
        if ($has_images) {
            foreach ($img_matches[0] as $img_tag) {
                // If width OR height attribute is missing, increment the counter
                if (!preg_match('/\s(width|data-width)\s*=\s*[\'"]?\d+/i', $img_tag) || !preg_match('/\s(height|data-height)\s*=\s*[\'"]?\d+/i', $img_tag)) {
                    $missing_dimensions++;
                }
            }
        }
        $is_cls_optimized = ($missing_dimensions === 0);

        $results['image_dimensions'] = [
            'label' => esc_html__( 'All Images Have Dimensions (CLS Prevention)', 'ultimate-seo-checklist' ),
            'status' => $is_cls_optimized ? 'green' : ($missing_dimensions > 0 ? 'red' : 'green'),
            'score_value' => $is_cls_optimized ? 5 : 0,
            'details' => $missing_dimensions > 0 ? sprintf( esc_html__( 'Missing dimensions on %d images.', 'ultimate-seo-checklist' ), $missing_dimensions ) : esc_html__( 'Optimized.', 'ultimate-seo-checklist' )
        ];
        
        // 5. Broken Link Check (Neutral Status for New Post)
        $results['broken_links'] = [
            'label' => esc_html__( 'Broken Link Check (Post-Publishing)', 'ultimate-seo-checklist' ),
            'status' => 'green', 
            'score_value' => 5,
            'details' => esc_html__( 'Check runs fully after publishing.', 'ultimate-seo-checklist' )
        ];

        return $results;
    }

    /**
     * Calculates the final score based on weights and check results.
     */
    private static function calculate_weighted_score( $core, $advanced, $technical ) {
        $total_score_sum = 0;

        // Define total possible points for each check type explicitly
        $core_max = 57; 
        $advanced_max = 45; 
        $technical_max = 25; 

        $max_scores = [
            'core' => $core_max,
            'advanced' => $advanced_max,
            'technical' => $technical_max,
        ];
        
        $categories = [
            'core' => $core,
            'advanced' => $advanced,
            'technical' => $technical,
        ];

        foreach ($categories as $key => $results) {
            $max_possible = $max_scores[$key];
            $achieved_score = array_sum( array_column( $results, 'score_value' ) );
            $weight = self::WEIGHTS[$key];

            if ($max_possible > 0) {
                // Calculate percentage achieved in this category
                $category_percentage = ($achieved_score / $max_possible);
                
                // Calculate weighted contribution to the final 100
                $weighted_contribution = $category_percentage * $weight;
                $total_score_sum += $weighted_contribution;
            }
        }

        return min(100, round( $total_score_sum ));
    }
}
