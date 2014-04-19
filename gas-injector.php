<?php
/*
 Plugin Name: GAS Injector
 Plugin URI: http://www.geckosolutions.se/blog/wordpress-plugins/
 Description: GAS Injector for WordPress will help you add Google Analytics on Steroids (GAS) to your WordPress blog.
 This will not only add basic Google Analytics tracking but also let you track which outbound links your visitors click on,
 how they use your forms, which movies they are watching, how far down on the page do they scroll etc. This and more you get by using GAS Injector for Wordpress.
 Just add your Google Analytics tracking code and your domain and you are done!
 Version: 1.3.2
 Author: Niklas Olsson
 Author URI: http://www.geckosolutions.se
 License: GPL 3.0, @see http://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Loads jQuery if not loaded.
 */
wp_enqueue_script('jquery');

/**
 * WP Hooks
 **/
add_action('init', 'load_gas_injector_translation_file');
add_action('wp_head', 'insert_google_analytics_code_and_domain');
add_action('admin_head', 'admin_register_gas_for_wordpress_head');
add_action('admin_menu', 'add_gas_injector_options_admin_menu');

/**
 * Loads the translation file for this plugin.
 */
function load_gas_injector_translation_file() {
    $plugin_path = basename(dirname(__FILE__));
    load_plugin_textdomain('gas-injector', null, $plugin_path . '/languages/');
}

/**
 * Insert the stylesheet and javascripts in the admin head.
 */
function admin_register_gas_for_wordpress_head() {
    $wp_content_url = get_option('siteurl');
    if(is_ssl()) {
        $wp_content_url = str_replace('http://', 'https://', $wp_content_url);
    }

    $plugin_url = $wp_content_url . '/wp-content/plugins/' . basename(dirname(__FILE__));
    $css_url = $plugin_url.'/css/gas-injector.css';
    $js_url = $plugin_url.'/js/gas-injector.js';
    echo "<link rel='stylesheet' type='text/css' href='$css_url' />\n".
        "<script type='text/javascript' src='$js_url'></script>\n";
}

/**
 * Inserts the Google Analytics tracking code and domain.
 */
function insert_google_analytics_code_and_domain() {
    if (!current_user_can('edit_posts')  && get_option('ua_tracking_code') != "") {
        echo "<!-- GAS Injector for Wordpress from http://www.geckosolutions.se/blog/wordpress-plugins/ -->\n";
        echo get_gas_tracking_code();
        echo "\n<!-- / GAS Injector for Wordpress -->\n";
    }
}

/**
 * Get the GAS tracking code based on the users given values for the UA tracking code
 * and the domain url.
 *
 * @param ua_tracking_code the UA-xxxx-x code from your Google Analytics account.
 * @param site_domain_url the url to use to determine the domain of the tracking.
 * @return the tracking code to render.
 */
function get_gas_tracking_code() {
    $code = "<script type='text/javascript'>";
    $code .= "var _gas = _gas || [];";

    if (get_option('debug') == 'on') {
        $code .= "_gas.push(['_setDebug', true]);";
    }

    $code .= "
    _gas.push(['_setAccount', '".get_option('ua_tracking_code')."']);
    _gas.push(['_setDomainName', '".get_option('site_domain_url')."']);
  ";

    if (get_option('anonymizeip') == 'on') {
        $code .= "_gas.push (['_gat._anonymizeIp']);";
    }

    $code .= "var pluginUrl = '//www.google-analytics.com/plugins/ga/inpage_linkid.js';";
    $code .= "_gas.push(['_require', 'inpage_linkid', pluginUrl]);";

    $code .= "
    _gas.push(['_trackPageview']);
  ";

    $code .= gas_injector_render_tracking_option('_gasTrackOutboundLinks', get_option('track_outbound_links'), get_option('outbound_links_category'));
    $code .= gas_injector_render_tracking_option('_gasTrackForms', get_option('track_forms'), get_option('forms_category'));
    $code .= gas_injector_render_tracking_option('_gasTrackMaxScroll', get_option('track_scroll'), get_option('scrolling_category'));
    $code .= gas_injector_render_tracking_option('_gasTrackDownloads', get_option('track_downloads'), get_option('downloads_category'));
    $code .= gas_injector_render_tracking_option('_gasTrackMailto', get_option('track_mailto_links'), get_option('mailto_links_category'));

    $code .= gas_injector_render_video_tracking_option('_gasTrackYoutube', get_option('track_youtube'), get_option('youtube_category'), "[25, 50, 75, 90]");
    $code .= gas_injector_render_video_tracking_option('_gasTrackVimeo', get_option('track_vimeo'), get_option('vimeo_category'), '');

    $code .= gas_injector_render_hooks(get_option('gas_hooks'));

    if (get_option('dcjs') == 'on') {
        $dcjs = "true";
    } else {
        $dcjs = "false";
    }
    $code .= "
    (function() {
    var ga = document.createElement('script');
    ga.id = 'gas-script';
    ga.setAttribute('data-use-dcjs', '".$dcjs."'); // CHANGE TO TRUE FOR DC.JS SUPPORT
    ga.type = 'text/javascript';
    ga.async = true;
    ga.src = '//cdnjs.cloudflare.com/ajax/libs/gas/1.11.0/gas.min.js';
    var s = document.getElementsByTagName('script')[0];
    s.parentNode.insertBefore(ga, s);
  })();
  </script>";

    return $code;
}

/**
 * Render the code for GAS hooks.
 *
 * @param $gas_hooks the custom code for gas hooks.
 */
function gas_injector_render_hooks($gas_hooks) {

    if(!gas_injector_isNullOrEmpty($gas_hooks)) {
        return $gas_hooks;
    } else {
        return "";
    }
}

/**
 * Render video tracking code.
 *
 * @param string $trackType type of tracking eg. _gasTrackYoutube
 * @param string $option option if this tracking should be disabled or not.
 * @param string $category custom category label.
 * @param string $percentages Tracking levels for Youtube video only.
 */
function gas_injector_render_video_tracking_option($trackType, $option, $category, $percentages) {
    $result = "";
    if (gas_injector_isNullOrEmpty($option)) {

        $result .= "_gas.push(['".$trackType."', {";

        if (!gas_injector_isNullOrEmpty($category)) {
            $result .= "category: '".$category."', ";
        }

        if (!gas_injector_isNullOrEmpty($percentages)) {
            $result .= "percentages: ".$percentages.", ";
        }
        $result .=  "force: true
    }]);";
    }
    return  $result;
}

/**
 * Render the tracking option based on the type, opton and category.
 *
 * @param string $trackType type of tracking eg. _gasTrackDownloads
 * @param string $option option if this tracking should be disabled or not.
 * @param string $category custom category label.
 */
function gas_injector_render_tracking_option($trackType, $option, $category) {
    $result = "";
    if (gas_injector_isNullOrEmpty($option)) {
        if (!gas_injector_isNullOrEmpty($category)) {
            $result = "_gas.push(['".$trackType."', {
        category: '".$category."' 
      }]);";
        } else {
            $result = "_gas.push(['".$trackType."']);";
        }
    }
    return $result;
}

/**
 * Check if the given value is null or an empty string.
 * @param string the given string to evaluate.
 */
function gas_injector_isNullOrEmpty($val) {
    if(is_null($val)) {
        return true;
    } else if ($val == "") {
        return true;
    } else {
        return false;
    }
}

/**
 * Gets the gas-x.x-min.js file.
 */
function gas_injector_getGASFile() {
    return path_join(WP_PLUGIN_URL, basename(dirname(__FILE__))."/js/gas-1.10.1.min.js");
}

/**
 * Add the plugin options page link to the dashboard menu.
 */
function add_gas_injector_options_admin_menu() {
    add_options_page(__('GAS Injector', 'gas-injector'), __('GAS Injector', 'gas-injector'), 'manage_options', basename(__FILE__), 'gas_injector_plugin_options_page');
}

/**
 * The main function that generate the options page for this plugin.
 */
function gas_injector_plugin_options_page() {

    $tracking_code_err = "";
    if(!isset($_POST['update_gas_for_wordpress_plugin_options'])) {
        $_POST['update_gas_for_wordpress_plugin_options'] == 'false';
    }

    if ($_POST['update_gas_for_wordpress_plugin_options'] == 'true') {

        $errors = gas_injector_plugin_options_update();

        if (is_wp_error($errors)) {
            $tracking_code_err = $errors->get_error_message('tracking_code');
        }
    }
    ?>
    <div class="wrap">
        <div class="gai-col1">
            <div id="icon-themes" class="icon32"><br /></div>
            <h2><?php echo __('GAS Injector for WordPress', 'gas-injector'); ?></h2>
            <?php
            if (!gas_injector_isNullOrEmpty(get_option('ua_tracking_code'))) {
                if(!gas_injector_is_valid_ga_code()) {
                    echo "<div class='errorContainer'>
                               <h3 class='errorMsg'>".__('Multiple Google Analytics scripts detected!.', 'gas-injector')."</h3>
                               <p class='errorMsg'>".__('Maybe you have several Google analytics plugins active or a hard coded Google Analytics script in your theme (header.php).', 'gas-injector')."</p>
                             </div>";
                }
            }
            ?>
            <form method="post" action="">

                <h4 style="margin-bottom: 0px;"><?php echo __('Google Analytics tracking code (UA-xxxx-x)', 'gas-injector'); ?></h4>
                <?php
                if ($tracking_code_err) {
                    echo '<div class="errorMsg">'.$tracking_code_err.'</div>';
                }
                ?>
                <input type="text" name="ua_tracking_code" id="ua_tracking_code" value="<?php echo get_option('ua_tracking_code'); ?>" />

                <h4 style="margin-bottom: 0px;"><?php echo __('Your domain eg. .mydomain.com', 'gas-injector'); ?></h4>
                <input type="text" name="site_domain_url" id="site_domain_url" value="<?php echo get_option('site_domain_url'); ?>" />
                <br>
                <h2><?php echo __('Optional settings', 'gas-injector'); ?></h2>

                <?php
                gas_injector_render_admin_tracking_option("track_outbound_links", 'outbound_links_category', get_option('outbound_links_category'), __('Disable tracking of outbound links', 'gas-injector'), __('(Default label is "Outbound")', 'gas-injector'));
                gas_injector_render_admin_tracking_option("track_forms", "forms_category", get_option('forms_category'), __('Disable tracking of forms', 'gas-injector'), __('(Default label is "Form Tracking")', 'gas-injector'));
                gas_injector_render_admin_tracking_option("track_mailto_links", "mailto_links_category", get_option('mailto_links_category'), __('Disable tracking of mailto links', 'gas-injector'), __('(Default label is "Mailto")', 'gas-injector'));
                gas_injector_render_admin_tracking_option("track_scroll", "scrolling_category", get_option('scrolling_category'), __('Disable tracking of scrolling', 'gas-injector'), __('(Default label is "MaxScroll")', 'gas-injector'));
                gas_injector_render_admin_tracking_option("track_downloads", "downloads_category", get_option('downloads_category'), __('Disable tracking of downloads', 'gas-injector'), __('(Default label is "Download")', 'gas-injector'));
                gas_injector_render_admin_tracking_option("track_youtube", "youtube_category", get_option('youtube_category'), __('Disable tracking of Youtube video', 'gas-injector'), __('(Default label is "Youtube Video")', 'gas-injector'));
                gas_injector_render_admin_tracking_option("track_vimeo", "vimeo_category", get_option('vimeo_category'), __('Disable tracking of Vimeo video', 'gas-injector'), __('(Default label is "Vimeo Video")', 'gas-injector'));
                ?>

                <h2><?php echo __('DC.JS Support', 'gas-injector'); ?></h2>

                <div class="gasOption">
                    <h4><input name="dcjs" type="checkbox" id="dcjs" <?php echo gas_injector_get_checked(get_option('dcjs')); ?> /> <?php echo __('Activate DC.JS support', 'gas-injector'); ?></h4>
                    <p><?php echo __('The DC.JS option add support for Display Advertising (Remarketing with Google Analytics).', 'gas-injector'); ?></p>
                </div>

                <h2><?php echo __('Anonymize IP', 'gas-injector'); ?></h2>

                <div class="gasOption">
                    <h4><input name="anonymizeip" type="checkbox" id="anonymizeip" <?php echo gas_injector_get_checked(get_option('anonymizeip')); ?> /> <?php echo __('Activate anonymized ip address', 'gas-injector'); ?></h4>
                    <p><?php echo __('The anonymize ip option truncate the visitors ip address, eg. anonymize the information sent by the tracker before storing it in Google Analytics.', 'gas-injector'); ?></p>
                </div>

                <h2><?php echo __('Debug settings', 'gas-injector'); ?></h2>

                <div class="gasOption">
                    <h4><input name="debug" type="checkbox" id="debug" <?php echo gas_injector_get_checked(get_option('debug')); ?> /> <?php echo __('Activate debug mode', 'gas-injector'); ?></h4>
                    <p><?php echo __('The debug mode help you test the analytics setup and to see that the events are triggered.', 'gas-injector'); ?></p>
                </div>

                <h2><?php echo __('Advanced features', 'gas-injector'); ?></h2>

                <h4 style="margin-bottom: 0px;"><?php echo __('Add code for GAS hooks: (eg. _gas.push([\'_gasTrackAudio\']);)', 'gas-injector'); ?></h4>
                <textarea rows="10" cols="70" name="gas_hooks" id="gas_hooks"><?php echo get_option('gas_hooks'); ?></textarea>
                <br>

                <input type="hidden" name="update_gas_for_wordpress_plugin_options" value="true" />
                <p><input type="submit" name="search" value="<?php echo __('Update Options', 'gas-injector'); ?>" class="button" /></p>

            </form>
        </div>
        <div class="gai-col2">

            <div class="description">
                <h3><?php echo __('Get going', 'gas-injector'); ?></h3>
                <?php
                $images_path = path_join(WP_PLUGIN_URL, basename(dirname(__FILE__))."/images/");
                $external_icon = '<img src="'.$images_path.'external_link_icon.png" title="External link" />';
                printf(__('Enter the tracking code from the Google Analytics account you want to use for this site. None of the java script code will be inserted if you leave this field empty. (eg. the plugin will be inactive)  Go to <a href="http://www.google.com/analytics/" target="_blank">Google Analytics</a> %s and get your tracking code.', 'gas-injector'), $external_icon);
                ?>
            </div>

            <div class="description">
                <?php echo __('This plugin exclude the visits from the Administrator if he/she is currently logged in.', 'gas-injector'); ?>
            </div>

            <div class="description">
                <h4><?php echo __('Optional settings', 'gas-injector'); ?></h4>
                <?php echo __('With the optional settings you can specify which of these different tracking features you want to use. All methods are active as default. You can also add custom labels for the categories i Google Analytics.', 'gas-injector'); ?>
            </div>

            <div class="description">
                <h4><?php echo __('Advanced features', 'gas-injector'); ?></h4>
                <p><?php echo __('In the section "Add code for GAS hooks" you can add more GSA hooks for additional tracking. eg. _gas.push([\'_gasTrackAudio\']); ', 'gas-injector'); ?></p>
            </div>

            <div class="description">
                <h4><?php echo __('Author', 'gas-injector'); ?></h4>
                <?php printf(__('This plugin is created by Gecko Solutions. Find more plugins at <a href="http://www.geckosolutions.se/blog/wordpress-plugins/">Gecko Solutions plugins</a> %s', 'gas-injector'), $external_icon); ?>
            </div>

        </div>
    </div>
<?php
}

/**
 * Gets the 'checked' string if the given option value is 'on'.
 * @param $value the option value to check
 */
function gas_injector_get_checked($value) {
    if($value=='on') {
        return 'checked';
    } else {
        return $value;
    }
}

/**
 * Gets the 'disabled' string if the given option value is 'on'.
 * @param $value the option value to check
 */
function gas_injector_is_disabled($value) {
    if($value=='on') {
        return "disabled";
    } else {
        return "";
    }
}

/**
 * Render the option markup for the given tracking option.
 *
 * @param string $checkboxOpt name and id of the input checkbox.
 * @param string $category name and id of the text input.
 * @param string $categoryOpt name of the given cutom category.
 * @param string $label the checkbox label for current tracking option.
 * @param string $defaultCategory description for the default category.
 */
function gas_injector_render_admin_tracking_option($checkboxOpt, $category, $categoryOpt, $label, $defaultCategory) {
    echo "<div class='gasOption'>".
        "<div class='trackBox'><input class='cBox' name='".$checkboxOpt."' type='checkbox' id='".$checkboxOpt."' ".gas_injector_get_checked(get_option($checkboxOpt))." /> <span class='checkboxLabel'>".$label."</span></div>".
        "<span class='label ".gas_injector_is_disabled(get_option($checkboxOpt))."'>".__('Custom label:', 'gas-injector')."</span>".
        "<input type='text' name='".$category."' id='".$category."' value='".$categoryOpt."' class='".gas_injector_is_disabled(get_option($checkboxOpt))."' ".gas_injector_is_disabled(get_option($checkboxOpt))." />".
        "<span class='categoryText ". gas_injector_is_disabled(get_option($checkboxOpt))."'>".$defaultCategory."</span>".
        "</div>";
}

/**
 * Update the GAS Injector plugin options.
 */
function gas_injector_plugin_options_update() {

    if(isset($_POST['ua_tracking_code'])) {
        update_option('ua_tracking_code', $_POST['ua_tracking_code']);
    }

    if(isset($_POST['ua_tracking_code']) && !gas_injector_isValidUaCode($_POST['ua_tracking_code'])) {
        $errors = new WP_Error('tracking_code', __('The tracking code is on the wrong format', 'gas-injector'));
    }

    if(isset($_POST['site_domain_url'])) {
        update_option('site_domain_url', $_POST['site_domain_url']);
    }

    if(isset($_POST['outbound_links_category'])) {
        update_option('outbound_links_category', $_POST['outbound_links_category']);
    }

    if(isset($_POST['forms_category'])) {
        update_option('forms_category', $_POST['forms_category']);
    }

    if(isset($_POST['mailto_links_category'])) {
        update_option('mailto_links_category', $_POST['mailto_links_category']);
    }

    if(isset($_POST['scrolling_category'])) {
        update_option('scrolling_category', $_POST['scrolling_category']);
    }

    if(isset($_POST['downloads_category'])) {
        update_option('downloads_category', $_POST['downloads_category']);
    }

    if(isset($_POST['youtube_category'])) {
        update_option('youtube_category', $_POST['youtube_category']);
    }

    if(isset($_POST['vimeo_category'])) {
        update_option('vimeo_category', $_POST['vimeo_category']);
    }

    if(isset($_POST['gas_hooks'])) {
        update_option('gas_hooks', stripslashes($_POST['gas_hooks']));
    }

    update_option('track_outbound_links', $_POST['track_outbound_links']);
    update_option('track_forms', $_POST['track_forms']);
    update_option('track_mailto_links', $_POST['track_mailto_links']);
    update_option('track_scroll', $_POST['track_scroll']);
    update_option('track_downloads', $_POST['track_downloads']);
    update_option('track_youtube', $_POST['track_youtube']);
    update_option('track_vimeo', $_POST['track_vimeo']);
    update_option('dcjs', $_POST['dcjs']);
    update_option('anonymizeip', $_POST['anonymizeip']);
    update_option('debug', $_POST['debug']);

    return $errors;
}

/**
 * Validate the format of the given Google Analytics tracking code.
 * @param $ua_tracking_code the given Google Analytics tracking code to validate.
 */
function gas_injector_isValidUaCode($ua_tracking_code) {
    if($ua_tracking_code == "" || preg_match('/^UA-\d{4,9}-\d{1,2}$/', $ua_tracking_code)) {
        return true;
    }
    return false;
}

/**
 * Make sure we only load Google Analytics one time.
 */
function gas_injector_is_valid_ga_code() {

    $body_content = gas_injector_get_site_content();
    $numRes = preg_match_all("/".get_option('ua_tracking_code')."/", $body_content, $matches);

    if($numRes > 1) {
        return false;
    } else {
        return true;
    }
}

/**
 * Get the site content.
 *
 * @param $url the given url.
 */
function gas_injector_get_site_content() {

    if (!function_exists('curl_init')){
        die(__('cURL is not installed', 'gas-injector'));
    }

    $connection = curl_init();

    curl_setopt($connection,CURLOPT_URL, site_url());
    curl_setopt($connection,CURLOPT_RETURNTRANSFER, true);
    curl_setopt($connection,CURLOPT_CONNECTTIMEOUT, 6);

    $content = curl_exec($connection);
    curl_close($connection);

    return $content;
} 