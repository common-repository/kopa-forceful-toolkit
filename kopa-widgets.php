<?php
add_action('widgets_init', 'kopa_widgets_plugin_init');
function kopa_widgets_plugin_init(){
register_widget('KopaAwesomeWeatherWidget');
register_widget('KopaCustomAwesomeWeatherWidget');
}
/**
 * Awesome Weather Widget
 * Modified by Kopatheme
 * @author Hal Gatewood
 * @version 1.3
 */
// WEATHER WIDGET: THE LOGIC
function kopa_awesome_weather_logic( $atts )
{
    $rtn                = "";
    $weather_data       = array();
    $location           = isset($atts['location']) ? $atts['location'] : false;
    $size               = (isset($atts['size']) AND $atts['size'] == "tall") ? 'tall' : 'wide';
    $units              = (isset($atts['units']) AND strtoupper($atts['units']) == "C") ? "metric" : "imperial";
    $units_display      = $units == "metric" ? __('C', kopa_plugin_get_domain()) : __('F', kopa_plugin_get_domain());
    $override_title     = isset($atts['override_title']) ? $atts['override_title'] : false;
    $days_to_show       = isset($atts['forecast_days']) ? $atts['forecast_days'] : 4;
    $show_stats         = (isset($atts['hide_stats']) AND $atts['hide_stats'] == 1) ? 0 : 1;
    $show_link          = (isset($atts['show_link']) AND $atts['show_link'] == 1) ? 1 : 0;
    $background         = isset($atts['background']) ? $atts['background'] : false;
    $locale             = 'en';

    $sytem_locale = get_locale();
    $available_locales = array( 'en', 'sp', 'fr', 'it', 'de', 'pt', 'ro', 'pl', 'ru', 'ua', 'fi', 'nl', 'bg', 'se', 'tr', 'zh_tw', 'zh_cn' ); 

    
    // CHECK FOR LOCALE
    if( in_array( $sytem_locale , $available_locales ) )
    {
        $locale = $sytem_locale;
    }
    
    // CHECK FOR LOCALE BY FIRST TWO DIGITS
    if( in_array(substr($sytem_locale, 0, 2), $available_locales ) )
    {
        $locale = substr($sytem_locale, 0, 2);
    }

    /**
     * GET CITY BY NAME AUTOMATICALLY
     */
    $geourl = "http://www.geoplugin.net/json.gp?ip=" . $_SERVER['REMOTE_ADDR'];

    $result = wp_remote_get( $geourl );
    if ( ! is_wp_error( $result ) && isset( $result['body'] ) ) {
        $result = json_decode( $result['body'] );
        
        if ( ! empty( $result->geoplugin_city ) && ! empty ( $result->geoplugin_countryName ) ) {
            $location = $result->geoplugin_city . ', ' . $result->geoplugin_countryName;
        } elseif ( ! empty( $result->geoplugin_city ) && empty ( $result->geoplugin_countryName ) ) {
            $location = $result->geoplugin_city;
        } elseif ( empty( $result->geoplugin_city ) && ! empty ( $result->geoplugin_countryName ) ) {
            $location = $result->geoplugin_countryName;
        }
    } else {
        return kopa_awesome_weather_error( $result->get_error_message()  );
    }

    // NO LOCATION, ABORT ABORT!!!1!
    if( !$location ) { return kopa_awesome_weather_error(); }
    
    
    //FIND AND CACHE CITY ID
    $city_name_slug                 = sanitize_title( $location );
    $weather_transient_name         = 'kopa-awesome-weather-' . $units . '-' . $city_name_slug . "-". $locale;


    // TWO APIS USED (VERSION 2.5)
    //http://api.openweathermap.org/data/2.5/weather?q=London,uk&units=metric&cnt=7&lang=fr
    //http://api.openweathermap.org/data/2.5/forecast/daily?q=London&units=metric&cnt=7&lang=fr

    // GET WEATHER DATA
    if( get_transient( $weather_transient_name ) )
    {
        $weather_data = get_transient( $weather_transient_name );
    }
    else
    {
        // NOW
        $now_ping = "http://api.openweathermap.org/data/2.5/weather?q=" . $city_name_slug . "&lang=" . $locale . "&units=" . $units;
        $now_ping_get = wp_remote_get( $now_ping );
    
        if( is_wp_error( $now_ping_get ) ) 
        {
            return kopa_awesome_weather_error( $now_ping_get->get_error_message()  ); 
        }   
    
        $city_data = json_decode( $now_ping_get['body'] );
        
        if( isset($city_data->cod) AND $city_data->cod == 404 )
        {
            return kopa_awesome_weather_error( $city_data->message ); 
        }
        else
        {
            $weather_data['now'] = $city_data;
        }
        
        
        // FORECAST
        if( $days_to_show != "hide" )
        {
            $forecast_ping = "http://api.openweathermap.org/data/2.5/forecast/daily?q=" . $city_name_slug . "&lang=" . $locale . "&units=" . $units ."&cnt=7";
            $forecast_ping_get = wp_remote_get( $forecast_ping );
        
            if( is_wp_error( $forecast_ping_get ) ) 
            {
                return kopa_awesome_weather_error( $forecast_ping_get->get_error_message()  ); 
            }   
            
            $forecast_data = json_decode( $forecast_ping_get['body'] );
            
            if( isset($forecast_data->cod) AND $forecast_data->cod == 404 )
            {
                return kopa_awesome_weather_error( $forecast_data->message ); 
            }
            else
            {
                $weather_data['forecast'] = $forecast_data;
            }
        }   
        
        
        if($weather_data['now'] AND $weather_data['forecast'])
        {
            // SET THE TRANSIENT, CACHE FOR AN HOUR
            set_transient( $weather_transient_name, $weather_data, apply_filters( 'kopa_auto_awesome_weather_cache', 3600 ) ); 
        }
    }



    // NO WEATHER
    if( !$weather_data OR !isset($weather_data['now'])) { return kopa_awesome_weather_error(); }
    
    
    // TODAYS TEMPS
    $today          = $weather_data['now'];
    $today_temp     = round($today->main->temp);
    $today_high     = round($today->main->temp_max);
    $today_low      = round($today->main->temp_min);
    
    
    // COLOR OF WIDGET
    $bg_color = "temp1";
    if($units_display == "F")
    {
        if($today_temp > 31 AND $today_temp < 40) $bg_color = "temp2";
        if($today_temp >= 40 AND $today_temp < 50) $bg_color = "temp3";
        if($today_temp >= 50 AND $today_temp < 60) $bg_color = "temp4";
        if($today_temp >= 60 AND $today_temp < 80) $bg_color = "temp5";
        if($today_temp >= 80 AND $today_temp < 90) $bg_color = "temp6";
        if($today_temp >= 90) $bg_color = "temp7";
    }
    else
    {
        if($today_temp > 1 AND $today_temp < 4) $bg_color = "temp2";
        if($today_temp >= 4 AND $today_temp < 10) $bg_color = "temp3";
        if($today_temp >= 10 AND $today_temp < 15) $bg_color = "temp4";
        if($today_temp >= 15 AND $today_temp < 26) $bg_color = "temp5";
        if($today_temp >= 26 AND $today_temp < 32) $bg_color = "temp6";
        if($today_temp >= 32) $bg_color = "temp7";
    }
    
    
    // DATA
    $header_title = $location;
    
    $today->main->humidity      = round($today->main->humidity);
    $today->wind->speed         = round($today->wind->speed);
    
    $wind_label = array ( 
                            __('N', kopa_plugin_get_domain()),
                            __('NNE', kopa_plugin_get_domain()), 
                            __('NE', kopa_plugin_get_domain()),
                            __('ENE', kopa_plugin_get_domain()),
                            __('E', kopa_plugin_get_domain()),
                            __('ESE', kopa_plugin_get_domain()),
                            __('SE', kopa_plugin_get_domain()),
                            __('SSE', kopa_plugin_get_domain()),
                            __('S', kopa_plugin_get_domain()),
                            __('SSW', kopa_plugin_get_domain()),
                            __('SW', kopa_plugin_get_domain()),
                            __('WSW', kopa_plugin_get_domain()),
                            __('W', kopa_plugin_get_domain()),
                            __('WNW', kopa_plugin_get_domain()),
                            __('NW', kopa_plugin_get_domain()),
                            __('NNW', kopa_plugin_get_domain())
                        );
                        
    $wind_direction = $wind_label[ fmod((($today->wind->deg + 11) / 22.5),16) ];
    
    $show_stats_class = ($show_stats) ? "awe_with_stats" : "awe_without_stats";
    
    if($background) $bg_color = "darken";

    // alway use temp6
    $bg_color = "temp6";
    
    // DISPLAY WIDGET   
    $rtn .= "
    
        <div id=\"awesome-weather-{$city_name_slug}\" class=\"awesome-weather-wrap awecf {$bg_color} {$show_stats_class} awe_{$size}\">
    ";


    if($background) 
    { 
        $rtn .= "<div class=\"awesome-weather-cover\" style='background-image: url($background);'>";
        $rtn .= "<div class=\"awesome-weather-darken\">";
    }

    $rtn .= "
            <div class=\"awesome-weather-header\">{$header_title}</div>
            <div class=\"awesome-weather-left\">
            
            <div class=\"awesome-weather-current-temp\">
                $today_temp<sup>{$units_display}</sup>
            </div> <!-- /.awesome-weather-current-temp -->
    ";  

    if($days_to_show != "hide")
    {
        $rtn .= "<div class=\"awesome-weather-forecast awe_days_{$days_to_show} awecf\">";
        $c = 1;
        $dt_today = date_i18n('Ymd');
        $forecast = $weather_data['forecast'];
        $days_to_show = (int) $days_to_show;
        
        foreach( (array) $forecast->list as $forecast )
        {
            if( $dt_today >= date_i18n('Ymd', $forecast->dt)) continue;
            
            $forecast->temp = (int) $forecast->temp->day;
            $day_of_week = date_i18n('D', $forecast->dt);
            $rtn .= "
                <div class=\"awesome-weather-forecast-day\">
                    <div class=\"awesome-weather-forecast-day-temp\">{$forecast->temp}<sup>{$units_display}</sup></div>
                    <div class=\"awesome-weather-forecast-day-abbr\">$day_of_week</div>
                </div>
            ";
            if($c == $days_to_show) break;
            $c++;
        }
        $rtn .= " </div> <!-- /.awesome-weather-forecast -->";
        $rtn .= " </div> <!-- /.awesome-weather-left -->";
    }
    
    if($show_stats)
    {
        $speed_text = ($units == "metric") ? __('km/h', kopa_plugin_get_domain()) : __('mph', kopa_plugin_get_domain());
    
    
        $rtn .= "
                
                <div class=\"awesome-weather-todays-stats\">
                    <div class=\"awe_desc\">{$today->weather[0]->description}</div>
                    <div class=\"awe_humidty\">" . __('humidity:', kopa_plugin_get_domain()) . " {$today->main->humidity}% </div>
                    <div class=\"awe_wind\">" . __('wind:', kopa_plugin_get_domain()) . " {$today->wind->speed}" . $speed_text . " {$wind_direction}</div>
                    <div class=\"awe_highlow\"> "  .__('H', kopa_plugin_get_domain()) . " {$today_high} &bull; " . __('L', kopa_plugin_get_domain()) . " {$today_low} </div>  
                </div> <!-- /.awesome-weather-todays-stats -->
        ";
    }
    
    
    if($show_link AND isset($today->id))
    {
        $show_link_text = apply_filters('kopa_awesome_weather_extended_forecast_text' , __('extended forecast', kopa_plugin_get_domain()));

        $rtn .= "<div class=\"awesome-weather-more-weather-link\">";
        $rtn .= "<a href=\"http://openweathermap.org/city/{$today->id}\" target=\"_blank\">{$show_link_text}</a>";      
        $rtn .= "</div> <!-- /.awesome-weather-more-weather-link -->";
    }
    
    if($background) 
    { 
        $rtn .= "</div> <!-- /.awesome-weather-cover -->";
        $rtn .= "</div> <!-- /.awesome-weather-darken -->";
    }
    
    
    $rtn .= "</div> <!-- /.awesome-weather-wrap -->";
    return $rtn;
}


// WEATHER WIDGET: RETURN ERROR
function kopa_awesome_weather_error( $msg = false )
{
    if(!$msg) $msg = __('No weather information available', kopa_plugin_get_domain());
    return apply_filters( 'kopa_awesome_weather_error', "<!-- AWESOME WEATHER ERROR: " . $msg . " -->" );
}


// WEATHER WIDGET CLASS
class KopaAwesomeWeatherWidget extends WP_Widget 
{
    function KopaAwesomeWeatherWidget() { 
        $widget_ops = array( 'classname' => 'widget_awesomeweatherwidget clearfix', 'description' => __( 'Display Weather Widget base on customer location automatically', kopa_plugin_get_domain() ) );
        $control_ops = array( 'width' => 'auto', 'height' => 'auto' );
        parent::__construct( 'kopa_awesome_weather_widget', __( 'Kopa Weather Widget (Auto Detect)', kopa_plugin_get_domain() ), $widget_ops, $control_ops );
    }

    function widget($args, $instance) 
    {   
        extract( $args );
        
        $location           = isset($instance['location']) ? $instance['location'] : false;
        $override_title     = isset($instance['override_title']) ? $instance['override_title'] : false;
        $units              = isset($instance['units']) ? $instance['units'] : false;
        $size               = isset($instance['size']) ? $instance['size'] : false;
        $forecast_days      = isset($instance['forecast_days']) ? $instance['forecast_days'] : false;
        $hide_stats         = (isset($instance['hide_stats']) AND $instance['hide_stats'] == 1) ? 1 : 0;
        $show_link          = (isset($instance['show_link']) AND $instance['show_link'] == 1) ? 1 : 0;
        $background         = isset($instance['background']) ? $instance['background'] : false;

        echo $before_widget;
        echo kopa_awesome_weather_logic( array( 'location' => $location, 'override_title' => $override_title, 'size' => $size, 'units' => $units, 'forecast_days' => $forecast_days, 'hide_stats' => $hide_stats, 'show_link' => $show_link, 'background' => $background ));
        echo $after_widget;
    }
 
    function update($new_instance, $old_instance) 
    {       
        $instance = $old_instance;
        // $instance['location']           = strip_tags($new_instance['location']);
        // $instance['override_title']     = strip_tags($new_instance['override_title']);
        $instance['units']              = strip_tags($new_instance['units']);
        $instance['forecast_days']      = strip_tags($new_instance['forecast_days']);
        $instance['show_link']          = strip_tags($new_instance['show_link']);
        return $instance;
    }
 
    function form($instance) 
    {   
        // $location           = isset($instance['location']) ? esc_attr($instance['location']) : "";
        // $override_title     = isset($instance['override_title']) ? esc_attr($instance['override_title']) : "";
        // $selected_size      = isset($instance['size']) ? esc_attr($instance['size']) : "wide";
        $units              = (isset($instance['units']) AND strtoupper($instance['units']) == "C") ? "C" : "F";
        $forecast_days      = isset($instance['forecast_days']) ? esc_attr($instance['forecast_days']) : 4;
        // $hide_stats         = (isset($instance['hide_stats']) AND $instance['hide_stats'] == 1) ? 1 : 0;
        $show_link          = (isset($instance['show_link']) AND $instance['show_link'] == 1) ? 1 : 0;
        // $background         = isset($instance['background']) ? esc_attr($instance['background']) : "";
    ?>
                       
        <p>
          <label for="<?php echo $this->get_field_id('units'); ?>"><?php _e('Units:', kopa_plugin_get_domain()); ?></label>  &nbsp;
          <input id="<?php echo $this->get_field_id('units'); ?>" name="<?php echo $this->get_field_name('units'); ?>" type="radio" value="F" <?php if($units == "F") echo ' checked="checked"'; ?> /> F &nbsp; &nbsp;
          <input id="<?php echo $this->get_field_id('units'); ?>" name="<?php echo $this->get_field_name('units'); ?>" type="radio" value="C" <?php if($units == "C") echo ' checked="checked"'; ?> /> C
        </p>
        
        <p>
          <label for="<?php echo $this->get_field_id('forecast_days'); ?>"><?php _e('Forecast:', kopa_plugin_get_domain()); ?></label> 
          <select class="widefat" id="<?php echo $this->get_field_id('forecast_days'); ?>" name="<?php echo $this->get_field_name('forecast_days'); ?>">
            <option value="5"<?php if($forecast_days == 5) echo " selected=\"selected\""; ?>><?php _e( '5 Days', kopa_plugin_get_domain() ); ?></option>
            <option value="4"<?php if($forecast_days == 4) echo " selected=\"selected\""; ?>><?php _e( '4 Days', kopa_plugin_get_domain() ); ?></option>
            <option value="3"<?php if($forecast_days == 3) echo " selected=\"selected\""; ?>><?php _e( '3 Days', kopa_plugin_get_domain() ); ?></option>
            <option value="2"<?php if($forecast_days == 2) echo " selected=\"selected\""; ?>><?php _e( '2 Days', kopa_plugin_get_domain() ); ?></option>
            <option value="1"<?php if($forecast_days == 1) echo " selected=\"selected\""; ?>><?php _e( '1 Day', kopa_plugin_get_domain() ); ?></option>
          </select>
        </p>
        
        <p>
          <label for="<?php echo $this->get_field_id('show_link'); ?>"><?php _e('Link to OpenWeatherMap:', kopa_plugin_get_domain()); ?></label>  &nbsp;
          <input id="<?php echo $this->get_field_id('show_link'); ?>" name="<?php echo $this->get_field_name('show_link'); ?>" type="checkbox" value="1" <?php if($show_link) echo ' checked="checked"'; ?> />
        </p> 
       
        
        <?php 
    }
}

/**
 * Awesome Weather Widget base on admin settings
 * Modified by Kopatheme
 * @author Hal Gatewood
 * @version 1.3
 */
// THE LOGIC
function kopa_custom_awesome_weather_logic( $atts )
{
    $rtn                = "";
    $weather_data       = array();
    $location           = isset($atts['location']) ? $atts['location'] : false;
    $size               = (isset($atts['size']) AND $atts['size'] == "tall") ? 'tall' : 'wide';
    $units              = (isset($atts['units']) AND strtoupper($atts['units']) == "C") ? "metric" : "imperial";
    $units_display      = $units == "metric" ? __('C', kopa_plugin_get_domain()) : __('F', kopa_plugin_get_domain());
    $override_title     = isset($atts['override_title']) ? $atts['override_title'] : false;
    $days_to_show       = isset($atts['forecast_days']) ? $atts['forecast_days'] : 4;
    $show_stats         = (isset($atts['hide_stats']) AND $atts['hide_stats'] == 1) ? 0 : 1;
    $show_link          = (isset($atts['show_link']) AND $atts['show_link'] == 1) ? 1 : 0;
    $background         = isset($atts['background']) ? $atts['background'] : false;
    $locale             = 'en';

    $sytem_locale = get_locale();
    $available_locales = array( 'en', 'sp', 'fr', 'it', 'de', 'pt', 'ro', 'pl', 'ru', 'ua', 'fi', 'nl', 'bg', 'se', 'tr', 'zh_tw', 'zh_cn' ); 

    
    // CHECK FOR LOCALE
    if( in_array( $sytem_locale , $available_locales ) )
    {
        $locale = $sytem_locale;
    }
    
    // CHECK FOR LOCALE BY FIRST TWO DIGITS
    if( in_array(substr($sytem_locale, 0, 2), $available_locales ) )
    {
        $locale = substr($sytem_locale, 0, 2);
    }


    // NO LOCATION, ABORT ABORT!!!1!
    if( !$location ) { return kopa_custom_awesome_weather_error(); }
    
    
    //FIND AND CACHE CITY ID
    $city_name_slug                 = sanitize_title( $location );
    $weather_transient_name         = 'kopa-custom-awesome-weather-' . $units . '-' . $city_name_slug . "-". $locale;


    // TWO APIS USED (VERSION 2.5)
    //http://api.openweathermap.org/data/2.5/weather?q=London,uk&units=metric&cnt=7&lang=fr
    //http://api.openweathermap.org/data/2.5/forecast/daily?q=London&units=metric&cnt=7&lang=fr

    
    
    // GET WEATHER DATA
    if( get_transient( $weather_transient_name ) )
    {
        $weather_data = get_transient( $weather_transient_name );
    }
    else
    {
        // NOW
        $now_ping = "http://api.openweathermap.org/data/2.5/weather?q=" . $city_name_slug . "&lang=" . $locale . "&units=" . $units;
        $now_ping_get = wp_remote_get( $now_ping );
    
        if( is_wp_error( $now_ping_get ) ) 
        {
            return kopa_custom_awesome_weather_error( $now_ping_get->get_error_message()  ); 
        }   
    
        $city_data = json_decode( $now_ping_get['body'] );
        
        if( isset($city_data->cod) AND $city_data->cod == 404 )
        {
            return kopa_custom_awesome_weather_error( $city_data->message ); 
        }
        else
        {
            $weather_data['now'] = $city_data;
        }
        
        
        // FORECAST
        if( $days_to_show != "hide" )
        {
            $forecast_ping = "http://api.openweathermap.org/data/2.5/forecast/daily?q=" . $city_name_slug . "&lang=" . $locale . "&units=" . $units ."&cnt=7";
            $forecast_ping_get = wp_remote_get( $forecast_ping );
        
            if( is_wp_error( $forecast_ping_get ) ) 
            {
                return kopa_custom_awesome_weather_error( $forecast_ping_get->get_error_message()  ); 
            }   
            
            $forecast_data = json_decode( $forecast_ping_get['body'] );
            
            if( isset($forecast_data->cod) AND $forecast_data->cod == 404 )
            {
                return kopa_custom_awesome_weather_error( $forecast_data->message ); 
            }
            else
            {
                $weather_data['forecast'] = $forecast_data;
            }
        }   
        
        
        if($weather_data['now'] AND $weather_data['forecast'])
        {
            // SET THE TRANSIENT, CACHE FOR AN HOUR
            set_transient( $weather_transient_name, $weather_data, apply_filters( 'kopa_custom_awesome_weather_cache', 3600 ) ); 
        }
    }



    // NO WEATHER
    if( !$weather_data OR !isset($weather_data['now'])) { return kopa_custom_awesome_weather_error(); }
    
    
    // TODAYS TEMPS
    $today          = $weather_data['now'];
    $today_temp     = round($today->main->temp);
    $today_high     = round($today->main->temp_max);
    $today_low      = round($today->main->temp_min);
    
    
    // COLOR OF WIDGET
    $bg_color = "temp1";
    if($units_display == "F")
    {
        if($today_temp > 31 AND $today_temp < 40) $bg_color = "temp2";
        if($today_temp >= 40 AND $today_temp < 50) $bg_color = "temp3";
        if($today_temp >= 50 AND $today_temp < 60) $bg_color = "temp4";
        if($today_temp >= 60 AND $today_temp < 80) $bg_color = "temp5";
        if($today_temp >= 80 AND $today_temp < 90) $bg_color = "temp6";
        if($today_temp >= 90) $bg_color = "temp7";
    }
    else
    {
        if($today_temp > 1 AND $today_temp < 4) $bg_color = "temp2";
        if($today_temp >= 4 AND $today_temp < 10) $bg_color = "temp3";
        if($today_temp >= 10 AND $today_temp < 15) $bg_color = "temp4";
        if($today_temp >= 15 AND $today_temp < 26) $bg_color = "temp5";
        if($today_temp >= 26 AND $today_temp < 32) $bg_color = "temp6";
        if($today_temp >= 32) $bg_color = "temp7";
    }
    
    
    // DATA
    $header_title = $override_title ? $override_title : $today->name;
    
    $today->main->humidity      = round($today->main->humidity);
    $today->wind->speed         = round($today->wind->speed);
    
    $wind_label = array ( 
                            __('N', kopa_plugin_get_domain()),
                            __('NNE', kopa_plugin_get_domain()), 
                            __('NE', kopa_plugin_get_domain()),
                            __('ENE', kopa_plugin_get_domain()),
                            __('E', kopa_plugin_get_domain()),
                            __('ESE', kopa_plugin_get_domain()),
                            __('SE', kopa_plugin_get_domain()),
                            __('SSE', kopa_plugin_get_domain()),
                            __('S', kopa_plugin_get_domain()),
                            __('SSW', kopa_plugin_get_domain()),
                            __('SW', kopa_plugin_get_domain()),
                            __('WSW', kopa_plugin_get_domain()),
                            __('W', kopa_plugin_get_domain()),
                            __('WNW', kopa_plugin_get_domain()),
                            __('NW', kopa_plugin_get_domain()),
                            __('NNW', kopa_plugin_get_domain())
                        );
                        
    $wind_direction = $wind_label[ fmod((($today->wind->deg + 11) / 22.5),16) ];
    
    $show_stats_class = ($show_stats) ? "awe_with_stats" : "awe_without_stats";
    
    if($background) $bg_color = "darken";
    $bg_color = 'temp6'; // force temp6
    
    // DISPLAY WIDGET   
    $rtn .= "
    
        <div id=\"awesome-weather-{$city_name_slug}\" class=\"awesome-weather-wrap awecf {$bg_color} {$show_stats_class} awe_{$size}\">
    ";


    if($background) 
    { 
        $rtn .= "<div class=\"awesome-weather-cover\" style='background-image: url($background);'>";
        $rtn .= "<div class=\"awesome-weather-darken\">";
    }

    $rtn .= "
            <div class=\"awesome-weather-header\">{$header_title}</div>
            <div class=\"awesome-weather-left\">
            
            <div class=\"awesome-weather-current-temp\">
                $today_temp<sup>{$units_display}</sup>
            </div> <!-- /.awesome-weather-current-temp -->
    ";  

    if($days_to_show != "hide")
    {
        $rtn .= "<div class=\"awesome-weather-forecast awe_days_{$days_to_show} awecf\">";
        $c = 1;
        $dt_today = date_i18n('Ymd');
        $forecast = $weather_data['forecast'];
        $days_to_show = (int) $days_to_show;
        
        foreach( (array) $forecast->list as $forecast )
        {
            if( $dt_today >= date_i18n('Ymd', $forecast->dt)) continue;
            
            $forecast->temp = (int) $forecast->temp->day;
            $day_of_week = date_i18n('D', $forecast->dt);
            $rtn .= "
                <div class=\"awesome-weather-forecast-day\">
                    <div class=\"awesome-weather-forecast-day-temp\">{$forecast->temp}<sup>{$units_display}</sup></div>
                    <div class=\"awesome-weather-forecast-day-abbr\">$day_of_week</div>
                </div>
            ";
            if($c == $days_to_show) break;
            $c++;
        }
        $rtn .= " </div> <!-- /.awesome-weather-forecast -->";
        $rtn .= " </div> <!-- /.awesome-weather-left -->";
    }
    
    if($show_stats)
    {
        $speed_text = ($units == "metric") ? __('km/h', kopa_plugin_get_domain()) : __('mph', kopa_plugin_get_domain());
    
    
        $rtn .= "
                
                <div class=\"awesome-weather-todays-stats\">
                    <div class=\"awe_desc\">{$today->weather[0]->description}</div>
                    <div class=\"awe_humidty\">" . __('humidity:', kopa_plugin_get_domain()) . " {$today->main->humidity}% </div>
                    <div class=\"awe_wind\">" . __('wind:', kopa_plugin_get_domain()) . " {$today->wind->speed}" . $speed_text . " {$wind_direction}</div>
                    <div class=\"awe_highlow\"> "  .__('H', kopa_plugin_get_domain()) . " {$today_high} &bull; " . __('L', kopa_plugin_get_domain()) . " {$today_low} </div>  
                </div> <!-- /.awesome-weather-todays-stats -->
        ";
    }
    
    
    if($show_link AND isset($today->id))
    {
        $show_link_text = apply_filters('kopa_awesome_weather_extended_forecast_text' , __('extended forecast', kopa_plugin_get_domain()));

        $rtn .= "<div class=\"awesome-weather-more-weather-link\">";
        $rtn .= "<a href=\"http://openweathermap.org/city/{$today->id}\" target=\"_blank\">{$show_link_text}</a>";      
        $rtn .= "</div> <!-- /.awesome-weather-more-weather-link -->";
    }
    
    if($background) 
    { 
        $rtn .= "</div> <!-- /.awesome-weather-cover -->";
        $rtn .= "</div> <!-- /.awesome-weather-darken -->";
    }
    
    
    $rtn .= "</div> <!-- /.awesome-weather-wrap -->";
    return $rtn;
}


// RETURN ERROR
function kopa_custom_awesome_weather_error( $msg = false )
{
    if(!$msg) $msg = __('No weather information available', 'awesome-weather');
    return apply_filters( 'kopa_custom_awesome_weather_error', "<!-- AWESOME WEATHER ERROR: " . $msg . " -->" );
}



// TEXT BLOCK WIDGET
class KopaCustomAwesomeWeatherWidget extends WP_Widget 
{
    function KopaCustomAwesomeWeatherWidget() {
        $widget_ops = array( 'classname' => 'widget_awesomeweatherwidget clearfix', 'description' => __( 'Display Weather Widget base on admin settings', kopa_plugin_get_domain() ) );
        $control_ops = array( 'width' => 'auto', 'height' => 'auto' );
        parent::__construct( 'awesome_weather_widget', __( 'Kopa Weather Widget (Custom)', kopa_plugin_get_domain() ), $widget_ops, $control_ops );
    }

    function widget($args, $instance) 
    {   
        extract( $args );
        
        $location           = isset($instance['location']) ? $instance['location'] : false;
        $override_title     = isset($instance['override_title']) ? $instance['override_title'] : false;
        $units              = isset($instance['units']) ? $instance['units'] : false;
        $size               = false;
        $forecast_days      = isset($instance['forecast_days']) ? $instance['forecast_days'] : false;
        $hide_stats         = 0;
        $show_link          = (isset($instance['show_link']) AND $instance['show_link'] == 1) ? 1 : 0;
        $background         = false;

        echo $before_widget;
        echo kopa_custom_awesome_weather_logic( array( 'location' => $location, 'override_title' => $override_title, 'size' => $size, 'units' => $units, 'forecast_days' => $forecast_days, 'hide_stats' => $hide_stats, 'show_link' => $show_link, 'background' => $background ));
        echo $after_widget;
    }
 
    function update($new_instance, $old_instance) 
    {       
        $instance = $old_instance;
        $instance['location']           = strip_tags($new_instance['location']);
        $instance['override_title']     = strip_tags($new_instance['override_title']);
        $instance['units']              = strip_tags($new_instance['units']);
        $instance['forecast_days']      = strip_tags($new_instance['forecast_days']);
        $instance['show_link']          = strip_tags($new_instance['show_link']);
        return $instance;
    }
 
    function form($instance) 
    {   
        global $awesome_weather_sizes;
        
        $location           = isset($instance['location']) ? esc_attr($instance['location']) : "";
        $override_title     = isset($instance['override_title']) ? esc_attr($instance['override_title']) : "";
        $units              = (isset($instance['units']) AND strtoupper($instance['units']) == "C") ? "C" : "F";
        $forecast_days      = isset($instance['forecast_days']) ? esc_attr($instance['forecast_days']) : 4;
        $show_link          = (isset($instance['show_link']) AND $instance['show_link'] == 1) ? 1 : 0;
    ?>
        <p>
          <label for="<?php echo $this->get_field_id('location'); ?>">
            <?php _e('Location:', 'awesome-weather'); ?><br />
            <small><?php _e('(i.e: London,UK or New York City,NY)', kopa_plugin_get_domain()); ?></small>
          </label> 
          <input class="widefat" style="margin-top: 4px;" id="<?php echo $this->get_field_id('location'); ?>" name="<?php echo $this->get_field_name('location'); ?>" type="text" value="<?php echo $location; ?>" />
        </p>
                
        <p>
          <label for="<?php echo $this->get_field_id('override_title'); ?>"><?php _e('Override Title:', kopa_plugin_get_domain()); ?></label> 
          <input class="widefat" id="<?php echo $this->get_field_id('override_title'); ?>" name="<?php echo $this->get_field_name('override_title'); ?>" type="text" value="<?php echo $override_title; ?>" />
        </p>
                
        <p>
          <label for="<?php echo $this->get_field_id('units'); ?>"><?php _e('Units:', kopa_plugin_get_domain()); ?></label>  &nbsp;
          <input id="<?php echo $this->get_field_id('units'); ?>" name="<?php echo $this->get_field_name('units'); ?>" type="radio" value="F" <?php if($units == "F") echo ' checked="checked"'; ?> /> F &nbsp; &nbsp;
          <input id="<?php echo $this->get_field_id('units'); ?>" name="<?php echo $this->get_field_name('units'); ?>" type="radio" value="C" <?php if($units == "C") echo ' checked="checked"'; ?> /> C
        </p>
        
        <p>
          <label for="<?php echo $this->get_field_id('forecast_days'); ?>"><?php _e('Forecast:', kopa_plugin_get_domain()); ?></label> 
          <select class="widefat" id="<?php echo $this->get_field_id('forecast_days'); ?>" name="<?php echo $this->get_field_name('forecast_days'); ?>">
            <option value="5"<?php if($forecast_days == 5) echo " selected=\"selected\""; ?>><?php _e( '5 Days', kopa_plugin_get_domain() ); ?></option>
            <option value="4"<?php if($forecast_days == 4) echo " selected=\"selected\""; ?>><?php _e( '4 Days', kopa_plugin_get_domain() ); ?></option>
            <option value="3"<?php if($forecast_days == 3) echo " selected=\"selected\""; ?>><?php _e( '3 Days', kopa_plugin_get_domain() ); ?></option>
            <option value="2"<?php if($forecast_days == 2) echo " selected=\"selected\""; ?>><?php _e( '2 Days', kopa_plugin_get_domain() ); ?></option>
            <option value="1"<?php if($forecast_days == 1) echo " selected=\"selected\""; ?>><?php _e( '1 Day', kopa_plugin_get_domain() ); ?></option>
          </select>
        </p>
        
        <p>
          <label for="<?php echo $this->get_field_id('show_link'); ?>"><?php _e('Link to OpenWeatherMap:', kopa_plugin_get_domain()); ?></label>  &nbsp;
          <input id="<?php echo $this->get_field_id('show_link'); ?>" name="<?php echo $this->get_field_name('show_link'); ?>" type="checkbox" value="1" <?php if($show_link) echo ' checked="checked"'; ?> />
        </p> 
       
        
        <?php 
    }
}

